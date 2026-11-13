<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../config/database.php';
use RumboAlCielo\Domain\Ports\PlatoCatalogRepository;
/** Adaptador PDO para consultar el catálogo utilizado al publicar menús. */
class PlatoCatalogo implements PlatoCatalogRepository
{
    private PDO $db;
    public function __construct() { $this->db=Database::getConnection(); }
    public function listarCatalogo(): array
    {
        $q=$this->db->query('SELECT id,nombre,tipo,activo FROM platos ORDER BY nombre');
        $out=[]; foreach($q->fetchAll(PDO::FETCH_ASSOC) as $p)$out[(int)$p['id']]=$p; return $out;
    }
}
