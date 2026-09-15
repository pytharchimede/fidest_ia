<?php

declare(strict_types=1);

namespace FidestIA\Repositories;

use PDO;

final class ApiLogRepository
{
    public function __construct(private readonly PDO $db) {}

    public function record(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO api_request_logs (api_client_id,request_id,method,route,status_code,duration_ms,ip_address,user_agent,document_uuid,error_code) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$data['api_client_id'] ?? null,$data['request_id'],$data['method'],$data['route'],$data['status_code'],$data['duration_ms'],$data['ip_address'] ?? null,$data['user_agent'] ?? null,$data['document_uuid'] ?? null,$data['error_code'] ?? null]);
    }

    public function latest(int $limit = 50, array $filters = []): array
    {
        $where=[];$params=[];
        foreach (['api_client_id'=>'l.api_client_id','status_code'=>'l.status_code'] as $key=>$column) {
            if (($filters[$key] ?? '') !== '') {$where[]="$column = ?";$params[]=(int)$filters[$key];}
        }
        if (($filters['route'] ?? '') !== '') {$where[]='l.route LIKE ?';$params[]='%'.$filters['route'].'%';}
        if (($filters['date'] ?? '') !== '') {$where[]='DATE(l.created_at) = ?';$params[]=$filters['date'];}
        $sql='SELECT l.*,c.name AS application_name FROM api_request_logs l LEFT JOIN api_clients c ON c.id=l.api_client_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY l.id DESC LIMIT ?';
        $stmt=$this->db->prepare($sql);$i=1;
        foreach($params as $value){$stmt->bindValue($i++,$value,is_int($value)?PDO::PARAM_INT:PDO::PARAM_STR);}
        $stmt->bindValue($i,$limit,PDO::PARAM_INT);$stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
