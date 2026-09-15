<?php

declare(strict_types=1);

namespace FidestIA\Repositories;

use PDO;

final class ValidationRuleRepository
{
    public function __construct(private readonly PDO $db) {}

    public function forDocumentType(int $documentTypeId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM validation_rules WHERE document_type_id = ? AND active = 1 ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$documentTypeId]);
        return $stmt->fetchAll();
    }

    public function saveResult(int $documentId, ?int $ruleId, bool $passed, string $severity, string $message, array $context = []): void
    {
        $stmt = $this->db->prepare('INSERT INTO document_validation_results (document_id, validation_rule_id, passed, severity, message, context) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $documentId,
            $ruleId,
            $passed ? 1 : 0,
            $severity,
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function all():array{return $this->db->query('SELECT vr.*,dt.name AS document_type_name,dt.code AS document_type_code FROM validation_rules vr JOIN document_types dt ON dt.id=vr.document_type_id ORDER BY dt.name,vr.sort_order,vr.id')->fetchAll()?:[];}
    public function create(array $data):int
    {
        $stmt=$this->db->prepare('INSERT INTO validation_rules (document_type_id,name,rule_type,field_name,scope_fields,parameters,error_message,severity,active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$data['document_type_id'],$data['name'],$data['rule_type'],$data['field_name']?:null,json_encode($data['scope_fields']??[],JSON_UNESCAPED_UNICODE),json_encode($data['parameters']??[],JSON_UNESCAPED_UNICODE),$data['error_message'],$data['severity']??'error',1,$data['sort_order']??100]);return (int)$this->db->lastInsertId();
    }
    public function setActive(int $id,bool $active):void{$stmt=$this->db->prepare('UPDATE validation_rules SET active=? WHERE id=?');$stmt->execute([$active?1:0,$id]);}
}
