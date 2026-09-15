<?php

declare(strict_types=1);

namespace FidestIA\Repositories;

use PDO;

final class DocumentRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findTypeByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM document_types WHERE code = ? AND active = 1 LIMIT 1');
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public function allTypes(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM document_types';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY CASE WHEN code = \'GENERAL\' THEN 0 ELSE 1 END, name ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function createType(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO document_types (code, name, description, extraction_schema, active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([
            strtoupper(trim((string) $data['code'])),
            trim((string) $data['name']),
            trim((string) ($data['description'] ?? '')) ?: null,
            json_encode($data['extraction_schema'] ?? ['fields' => [], 'keywords' => []], JSON_UNESCAPED_UNICODE),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateDocumentType(int $documentId, int $documentTypeId): void
    {
        $stmt = $this->db->prepare('UPDATE documents SET document_type_id = ? WHERE id = ?');
        $stmt->execute([$documentTypeId, $documentId]);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO documents
            (uuid, document_type_id, api_client_id, client_reference, original_name, stored_name, storage_path, mime_type, file_size, sha256, status)
            VALUES (:uuid, :document_type_id, :api_client_id, :client_reference, :original_name, :stored_name, :storage_path, :mime_type, :file_size, :sha256, :status)';
        $data['api_client_id'] = $data['api_client_id'] ?? null;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function updateAnalysis(int $id, string $text, array $extractedData, string $status, ?float $confidence): void
    {
        $stmt = $this->db->prepare('UPDATE documents SET extracted_text = ?, extracted_data = ?, status = ?, confidence = ? WHERE id = ?');
        $stmt->execute([$text, json_encode($extractedData, JSON_UNESCAPED_UNICODE), $status, $confidence, $id]);
    }

    public function updateIntelligence(int $id,string $normalizedText,array $anomalies,array $scores):void
    {
        $stmt=$this->db->prepare('UPDATE documents SET normalized_text=?,anomalies=?,confidence_scores=? WHERE id=?');$stmt->execute([$normalizedText,json_encode($anomalies,JSON_UNESCAPED_UNICODE),json_encode($scores,JSON_UNESCAPED_UNICODE),$id]);
        $stmt=$this->db->prepare('INSERT INTO document_history (document_id,event_type,new_status,details) VALUES (?,\'analysis_completed\',(SELECT status FROM documents WHERE id=?),?)');$stmt->execute([$id,$id,json_encode(['scores'=>$scores,'anomaly_count'=>count($anomalies)],JSON_UNESCAPED_UNICODE)]);
    }

    public function existsByField(int $documentTypeId, string $field, mixed $value, array $scope = []): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $sql = 'SELECT id, extracted_data FROM documents WHERE document_type_id = ? AND status <> \'error\'';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$documentTypeId]);

        while ($row = $stmt->fetch()) {
            $data = json_decode((string) ($row['extracted_data'] ?? '{}'), true) ?: [];
            if (($data[$field] ?? null) != $value) {
                continue;
            }
            $scopeMatches = true;
            foreach ($scope as $scopeField => $scopeValue) {
                if (($data[$scopeField] ?? null) != $scopeValue) {
                    $scopeMatches = false;
                    break;
                }
            }
            if ($scopeMatches) {
                return true;
            }
        }
        return false;
    }

    public function latest(int $limit = 20): array
    {
        $stmt = $this->db->prepare('SELECT d.*, dt.code AS document_type_code, dt.name AS document_type_name FROM documents d LEFT JOIN document_types dt ON dt.id=d.document_type_id ORDER BY d.id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search(array $filters = [], int $limit = 100): array
    {
        $where=[];$params=[];
        if(($filters['q']??'')!==''){$where[]='(d.uuid LIKE ? OR d.original_name LIKE ? OR d.client_reference LIKE ?)';$term='%'.$filters['q'].'%';array_push($params,$term,$term,$term);}
        foreach(['status'=>'d.status','document_type_id'=>'d.document_type_id','api_client_id'=>'d.api_client_id'] as $key=>$column){if(($filters[$key]??'')!==''){$where[]="$column=?";$params[]=$filters[$key];}}
        if(($filters['from']??'')!==''){$where[]='d.created_at>=?';$params[]=$filters['from'].' 00:00:00';}if(($filters['to']??'')!==''){$where[]='d.created_at<=?';$params[]=$filters['to'].' 23:59:59';}
        $sql='SELECT d.*,dt.code AS document_type_code,dt.name AS document_type_name,c.name AS application_name FROM documents d LEFT JOIN document_types dt ON dt.id=d.document_type_id LEFT JOIN api_clients c ON c.id=d.api_client_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY d.id DESC LIMIT ?';
        $stmt=$this->db->prepare($sql);$i=1;foreach($params as $value)$stmt->bindValue($i++,$value);$stmt->bindValue($i,$limit,PDO::PARAM_INT);$stmt->execute();return $stmt->fetchAll()?:[];
    }

    public function findByUuid(string $uuid): ?array
    {
        $stmt=$this->db->prepare('SELECT d.*,dt.code AS document_type_code,dt.name AS document_type_name,c.name AS application_name FROM documents d LEFT JOIN document_types dt ON dt.id=d.document_type_id LEFT JOIN api_clients c ON c.id=d.api_client_id WHERE d.uuid=?');$stmt->execute([$uuid]);$row=$stmt->fetch();if(!$row)return null;
        $stmt=$this->db->prepare('SELECT * FROM document_validation_results WHERE document_id=? ORDER BY id');$stmt->execute([$row['id']]);$row['validations']=$stmt->fetchAll()?:[];
        $stmt=$this->db->prepare('SELECT * FROM document_history WHERE document_id=? ORDER BY id DESC');$stmt->execute([$row['id']]);$row['history']=$stmt->fetchAll()?:[];return $row;
    }

    public function updateType(int $id,array $data):bool
    {
        $stmt=$this->db->prepare('UPDATE document_types SET name=?,description=?,extraction_schema=?,active=? WHERE id=?');$stmt->execute([$data['name'],$data['description']?:null,json_encode($data['extraction_schema'],JSON_UNESCAPED_UNICODE),$data['active']?1:0,$id]);return $stmt->rowCount()>0;
    }

    public function stats():array
    {
        $sql="SELECT COUNT(*) total,SUM(DATE(created_at)=CURDATE()) today,SUM(status='validated') validated,SUM(status='rejected') rejected,SUM(status='error') errors FROM documents";return $this->db->query($sql)->fetch()?:[];
    }

    public function correctField(string $uuid,string $field,mixed $correctedValue,string $correctedBy='admin'):bool
    {
        if(!preg_match('/^[a-z][a-z0-9_]{0,119}$/i',$field))return false;$document=$this->findByUuid($uuid);if(!$document)return false;$data=json_decode((string)($document['extracted_data']??'{}'),true)?:[];$detected=$data[$field]??null;$data[$field]=$correctedValue;
        $this->db->beginTransaction();try{$stmt=$this->db->prepare('UPDATE documents SET extracted_data=? WHERE id=?');$stmt->execute([json_encode($data,JSON_UNESCAPED_UNICODE),(int)$document['id']]);$stmt=$this->db->prepare('INSERT INTO document_field_corrections (document_id,field_name,detected_value,corrected_value,corrected_by) VALUES (?,?,?,?,?)');$stmt->execute([(int)$document['id'],$field,is_scalar($detected)?(string)$detected:json_encode($detected,JSON_UNESCAPED_UNICODE),(string)$correctedValue,$correctedBy]);$this->db->commit();return true;}catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }
}
