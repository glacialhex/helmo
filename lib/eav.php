<?php

class EAVService {
    public static function getAttributes(PDO $pdo, string $entityType = 'student'): array {
        $stmt = $pdo->prepare("SELECT * FROM eav_attributes WHERE entity_type = ? ORDER BY name");
        $stmt->execute([$entityType]);
        return $stmt->fetchAll();
    }

    public static function getStudents(PDO $pdo): array {
        return $pdo->query('SELECT id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
    }

    public static function listValues(PDO $pdo, string $entityType = 'student'): array {
        $sql = "SELECT ev.entity_type, ev.entity_id, ev.attribute_id,
                       ev.value_text, ev.value_number, ev.value_date, ev.value_bool,
                       ea.name, ev.data_type,
                       CONCAT(s.first_name,' ',s.last_name) AS student
                FROM eav_values_all ev
                JOIN eav_attributes ea ON ea.id = ev.attribute_id
                JOIN students s ON s.id = ev.entity_id
                WHERE ev.entity_type = ?
                ORDER BY s.first_name, ea.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$entityType]);
        return $stmt->fetchAll();
    }

    public static function addAttribute(PDO $pdo, string $entityType, string $name, string $dataType): void {
        $stmt = $pdo->prepare("INSERT INTO eav_attributes (entity_type, name, data_type) VALUES (?,?,?)");
        $stmt->execute([$entityType, $name, $dataType]);
    }

    public static function saveValue(PDO $pdo, string $entityType, int $entityId, int $attributeId, string $dataType, $value): void {
        switch ($dataType) {
            case 'number':
                $stmt = $pdo->prepare('INSERT INTO eav_values_number (entity_type, entity_id, attribute_id, value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
                $stmt->execute([$entityType, $entityId, $attributeId, (float)$value]);
                break;
            case 'date':
                $stmt = $pdo->prepare('INSERT INTO eav_values_date (entity_type, entity_id, attribute_id, value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
                $stmt->execute([$entityType, $entityId, $attributeId, $value]);
                break;
            case 'bool':
                $stmt = $pdo->prepare('INSERT INTO eav_values_bool (entity_type, entity_id, attribute_id, value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
                $stmt->execute([$entityType, $entityId, $attributeId, $value === '1' || $value === 1 ? 1 : 0]);
                break;
            case 'text':
            default:
                $stmt = $pdo->prepare('INSERT INTO eav_values_text (entity_type, entity_id, attribute_id, value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
                $stmt->execute([$entityType, $entityId, $attributeId, trim((string)$value)]);
                break;
        }
    }
}

?>
