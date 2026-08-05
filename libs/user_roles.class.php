<?php
// ============================================================
// libs/user_roles.class.php — Ruoli multipli per utente.
// Un utente puo' avere piu' ruoli (Esperto / Project manager / Consulente),
// memorizzati nella tabella user_roles (UNIQUE su user_id+role).
// ============================================================
class UserRoles
{
    const ROLES = ['expert', 'project_manager', 'consultant'];

    /** @var PDO */
    private $conn;

    public function __construct(PDO $conn) { $this->conn = $conn; }

    /** Verifica ruolo via PDO (per pagine che usano \$pdo, es. blog). */
    public static function hasRolePdo(\PDO $pdo, int $user_id, string $role): bool
    {
        $st = $pdo->prepare("SELECT 1 FROM `user_roles` WHERE user_id = :u AND role = :r LIMIT 1");
        $st->execute([':u' => $user_id, ':r' => $role]);
        return (bool) $st->fetchColumn();
    }

    /** Tutti i ruoli dell'utente (array di stringhe). */
    public function getRoles(int $user_id): array
    {
        $st = $this->conn->prepare("SELECT role FROM `user_roles` WHERE user_id = ? ORDER BY role");
        $st->execute([$user_id]);
        $res = $st;
        $out = [];
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) { $out[] = $r['role']; }
        return $out;
    }

    public function hasRole(int $user_id, string $role): bool
    {
        return in_array($role, $this->getRoles($user_id), true);
    }

    /** Aggiunge un ruolo (idempotente via UNIQUE). */
    public function addRole(int $user_id, string $role): bool
    {
        if (!in_array($role, self::ROLES, true)) { return false; }
        $st = $this->conn->prepare("INSERT IGNORE INTO `user_roles` (user_id, role) VALUES (?, ?)");
        return $st->execute([$user_id, $role]);
    }

    public function removeRole(int $user_id, string $role): bool
    {
        $st = $this->conn->prepare("DELETE FROM `user_roles` WHERE user_id = ? AND role = ?");
        return $st->execute([$user_id, $role]);
    }

    /** Utenti con un dato ruolo (per l'elenco PM/consulenti inviato alle aziende). */
    public function getUsersByRole(string $role): array
    {
        $st = $this->conn->prepare(
            "SELECT u.id_user, u.username, u.email, u.phone
               FROM `user_roles` r
               JOIN `users` u ON u.id_user = r.user_id
              WHERE r.role = ?
              ORDER BY u.username"
        );
        $st->execute([$role]);
        $res = $st;
        $out = [];
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) { $out[] = $r; }
        return $out;
    }
}
