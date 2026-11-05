<?php

declare(strict_types=1);

namespace Database\Seeds;

use App\Core\DB;
use PDO;

class DatabaseSeeder
{
    public static function run(bool $seedOwner = false, ?string $ownerPassword = null): void
    {
        $pdo = DB::pdo();

        echo "[SEED] Seeding roles...\n";
        self::seedRoles($pdo);

        echo "[SEED] Seeding species and breeds...\n";
        self::seedSpeciesAndBreeds($pdo);

        echo "[SEED] Seeding business units...\n";
        self::seedBusinessUnits($pdo);

        echo "[SEED] Seeding feed types...\n";
        self::seedFeedTypes($pdo);

        echo "[SEED] Seeding SMS templates...\n";
        self::seedSmsTemplates($pdo);

        echo "[SEED] Seeding incubation allocation rules...\n";
        self::seedIncubationAllocationRules($pdo);

        if ($seedOwner || $ownerPassword !== null) {
            echo "[SEED] Seeding owner user...\n";
            self::seedOwnerUser($pdo, $ownerPassword);
        }

        echo "[SEED] Seeding completed successfully.\n";
    }

    public static function seedRoles(PDO $pdo): void
    {
        $roles = [
            ['code' => 'owner', 'name' => 'Owner'],
            ['code' => 'manager', 'name' => 'Manager'],
            ['code' => 'worker', 'name' => 'Farm worker'],
            ['code' => 'accountant', 'name' => 'Accountant'],
            ['code' => 'viewer', 'name' => 'Viewer'],
        ];

        $stmt = $pdo->prepare("INSERT INTO roles (code, name) VALUES (:code, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }

    public static function seedSpeciesAndBreeds(PDO $pdo): void
    {
        $species = [
            ['id' => 1, 'code' => 'CHICKEN', 'name' => 'Chicken', 'incubation_days' => 21],
            ['id' => 2, 'code' => 'TURKEY', 'name' => 'Turkey', 'incubation_days' => 28],
            ['id' => 3, 'code' => 'DUCK', 'name' => 'Duck', 'incubation_days' => 28],
            ['id' => 4, 'code' => 'QUAIL', 'name' => 'Quail', 'incubation_days' => 18],
        ];

        $stmtSpecies = $pdo->prepare("INSERT INTO species (id, code, name, incubation_days) VALUES (:id, :code, :name, :incubation_days) ON DUPLICATE KEY UPDATE name = VALUES(name), incubation_days = VALUES(incubation_days)");
        foreach ($species as $item) {
            $stmtSpecies->execute($item);
        }

        $breeds = [
            ['species_id' => 1, 'code' => 'MIKOLONGWE', 'name' => 'Mikolongwe', 'egg_tray_size' => 30, 'egg_tray_price' => 27000],
            ['species_id' => 1, 'code' => 'SASSO', 'name' => 'Sasso', 'egg_tray_size' => 30, 'egg_tray_price' => 30000],
            ['species_id' => 1, 'code' => 'LOCAL', 'name' => 'Local chicken', 'egg_tray_size' => 30, 'egg_tray_price' => 27000],
            ['species_id' => 1, 'code' => 'KUROILER', 'name' => 'Kuroiler', 'egg_tray_size' => 30, 'egg_tray_price' => null],
            ['species_id' => 2, 'code' => 'TURKEY', 'name' => 'Turkey', 'egg_tray_size' => 30, 'egg_tray_price' => null],
            ['species_id' => 3, 'code' => 'PEKIN', 'name' => 'Pekin duck', 'egg_tray_size' => 30, 'egg_tray_price' => 40000],
            ['species_id' => 3, 'code' => 'KHAKI_CAMPBELL', 'name' => 'Khaki Campbell duck', 'egg_tray_size' => 30, 'egg_tray_price' => 40000],
            ['species_id' => 4, 'code' => 'QUAIL', 'name' => 'Quail', 'egg_tray_size' => 30, 'egg_tray_price' => 10000],
        ];

        $stmtBreeds = $pdo->prepare("INSERT INTO breeds (species_id, code, name, egg_tray_size, egg_tray_price) VALUES (:species_id, :code, :name, :egg_tray_size, :egg_tray_price) ON DUPLICATE KEY UPDATE name = VALUES(name), egg_tray_size = VALUES(egg_tray_size), egg_tray_price = VALUES(egg_tray_price)");
        foreach ($breeds as $breed) {
            $stmtBreeds->execute($breed);
        }
    }

    public static function seedBusinessUnits(PDO $pdo): void
    {
        $units = [
            ['code' => 'POULTRY', 'name' => 'Poultry', 'kind' => 'poultry'],
            ['code' => 'BEE', 'name' => 'Bee farming', 'kind' => 'bee'],
            ['code' => 'CAR_MZ9350', 'name' => 'Car hire MZ 9350', 'kind' => 'car_hire'],
            ['code' => 'CAR_NA7153', 'name' => 'Car hire NA 7153', 'kind' => 'car_hire'],
            ['code' => 'CAR_MZ8170', 'name' => 'Car hire MZ 8170', 'kind' => 'car_hire'],
            ['code' => 'CAR_MJ4324', 'name' => 'Car hire MJ 4324', 'kind' => 'car_hire'],
        ];

        $stmt = $pdo->prepare("INSERT INTO business_units (code, name, kind) VALUES (:code, :name, :kind) ON DUPLICATE KEY UPDATE name = VALUES(name), kind = VALUES(kind)");
        foreach ($units as $unit) {
            $stmt->execute($unit);
        }
    }

    public static function seedFeedTypes(PDO $pdo): void
    {
        $types = ['Layers mash', 'Pullet grower', 'Pullet starter', 'Chick feed', 'Growers mash'];
        $stmt = $pdo->prepare("INSERT INTO feed_types (name) VALUES (:name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
        foreach ($types as $name) {
            $stmt->execute(['name' => $name]);
        }
    }

    public static function seedSmsTemplates(PDO $pdo): void
    {
        $templates = [
            ['code' => 'JOB_RECEIVED', 'body' => 'Msantha Poultry: we received {eggs} eggs on {date}. Expected hatch: {hatch_date}. Cost MK{cost}.'],
            ['code' => 'TRANSFER_DAY', 'body' => 'Msantha Poultry: your {eggs} eggs moved to the hatching tray today. Hatch expected {hatch_date}.'],
            ['code' => 'HATCH_READY', 'body' => 'Msantha Poultry: {hatched} chicks hatched from your {eggs} eggs ({percent}%). Please collect. Balance MK{balance}.'],
            ['code' => 'PAYMENT_RECEIPT', 'body' => 'Msantha Poultry: received MK{amount} on {date}. Balance MK{balance}. Thank you.'],
            ['code' => 'BALANCE_REMINDER', 'body' => 'Msantha Poultry: you owe MK{balance} for incubation of {date}. Pay via {pay_link}'],
            ['code' => 'VACCINE_DUE', 'body' => 'Farm alert: {vaccine} due today for {batch}.'],
            ['code' => 'LOW_STOCK', 'body' => 'Farm alert: {batch} stock is {balance}. Deaths today {deaths}.'],
        ];

        $stmt = $pdo->prepare("INSERT INTO sms_templates (code, body) VALUES (:code, :body) ON DUPLICATE KEY UPDATE body = VALUES(body)");
        foreach ($templates as $tmpl) {
            $stmt->execute($tmpl);
        }
    }

    public static function seedIncubationAllocationRules(PDO $pdo): void
    {
        $rules = [
            ['category' => 'electricity', 'percent' => 25.00],
            ['category' => 'water', 'percent' => 15.00],
            ['category' => 'labour', 'percent' => 30.00],
            ['category' => 'telephone', 'percent' => 20.00],
            ['category' => 'profit', 'percent' => 10.00],
        ];

        $stmt = $pdo->prepare("INSERT INTO incubation_allocation_rules (category, percent) VALUES (:category, :percent) ON DUPLICATE KEY UPDATE percent = VALUES(percent)");
        foreach ($rules as $rule) {
            $stmt->execute($rule);
        }
    }

    public static function seedOwnerUser(PDO $pdo, ?string $password = null): void
    {
        if ($password === null || trim($password) === '') {
            throw new \InvalidArgumentException("Owner password cannot be empty. Password must be supplied via CLI prompt.");
        }

        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE code = 'owner' LIMIT 1");
        $roleStmt->execute();
        $roleId = (int) $roleStmt->fetchColumn();

        if ($roleId === 0) {
            throw new \RuntimeException("Owner role not found in roles table.");
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $pdo->prepare("
            INSERT INTO users (role_id, full_name, phone, email, username, password_hash, is_active)
            VALUES (:role_id, :full_name, :phone, :email, :username, :password_hash, 1)
            ON DUPLICATE KEY UPDATE
                password_hash = VALUES(password_hash),
                role_id = VALUES(role_id),
                full_name = VALUES(full_name),
                phone = VALUES(phone),
                email = VALUES(email),
                is_active = 1
        ");

        $stmt->execute([
            'role_id' => $roleId,
            'full_name' => 'Farm Owner',
            'phone' => '+265999899620',
            'email' => 'owner@msantha.mw',
            'username' => 'owner',
            'password_hash' => $hash,
        ]);
    }
}

