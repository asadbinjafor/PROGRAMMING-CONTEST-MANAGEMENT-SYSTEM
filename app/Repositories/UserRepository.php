<?php
declare(strict_types=1);

namespace PCMS\Repositories;

use PCMS\Support\Database;

final class UserRepository
{
    public function byEmail(string $email): ?array
    {
        $user = Database::one("SELECT user_id,user_name,email,password_hash,institution,rating,profile_image,account_status FROM app_user WHERE LOWER(email)=LOWER(:email)", ['email'=>$email]);
        return $user ? $this->withRoles($user) : null;
    }

    public function byId(int $id): ?array
    {
        $user = Database::one("SELECT user_id,user_name,email,institution,rating,profile_image,account_status,created_at FROM app_user WHERE user_id=:id", ['id'=>$id]);
        return $user ? $this->withRoles($user) : null;
    }

    public function create(array $data): int
    {
        return Database::transaction(function () use ($data): int {
            $id = (int)Database::scalar('SELECT seq_app_user.NEXTVAL FROM dual');
            Database::execute("INSERT INTO app_user (user_id,user_name,email,password_hash,institution,rating,account_status) VALUES (:id,:name,LOWER(:email),:hash,:institution,0,'ACTIVE')", [
                'id'=>$id,'name'=>$data['user_name'],'email'=>$data['email'],'hash'=>$data['password_hash'],'institution'=>$data['institution'] ?: null
            ], false);
            Database::execute("INSERT INTO user_role (user_id,role_id) SELECT :id,role_id FROM role WHERE role_code='PARTICIPANT'", ['id'=>$id], false);
            return $id;
        });
    }

    public function updateProfile(int $id, string $name, ?string $institution): void
    {
        Database::execute("UPDATE app_user SET user_name=:name,institution=:institution,updated_at=SYSTIMESTAMP WHERE user_id=:id", compact('name','institution','id'));
    }

    public function changePassword(int $id, string $hash): void
    {
        Database::execute("UPDATE app_user SET password_hash=:hash,updated_at=SYSTIMESTAMP WHERE user_id=:id", compact('hash','id'));
    }

    public function setRememberToken(int $id,string $selector,string $validatorHash,string $expiresAt): void
    {
        Database::execute("UPDATE app_user SET remember_selector=:selector,remember_validator_hash=:hash,remember_expires_at=TO_TIMESTAMP_TZ(:expires_at,'YYYY-MM-DD HH24:MI:SS TZH:TZM') WHERE user_id=:id",['selector'=>$selector,'hash'=>$validatorHash,'expires_at'=>$expiresAt,'id'=>$id]);
    }

    public function byRememberSelector(string $selector): ?array
    {
        $user=Database::one("SELECT user_id,user_name,email,institution,rating,profile_image,account_status,remember_validator_hash FROM app_user WHERE remember_selector=:selector AND remember_expires_at>SYSTIMESTAMP",['selector'=>$selector]);
        return $user?$this->withRoles($user):null;
    }

    public function clearRememberToken(int $id): void
    {
        Database::execute('UPDATE app_user SET remember_selector=NULL,remember_validator_hash=NULL,remember_expires_at=NULL WHERE user_id=:id',['id'=>$id]);
    }

    public function createResetToken(int $userId,string $selector,string $hash,string $expiresAt): void
    {
        Database::execute("INSERT INTO password_reset_token(token_id,user_id,selector,validator_hash,expires_at) VALUES(seq_password_reset.NEXTVAL,:user_id,:selector,:hash,TO_TIMESTAMP_TZ(:expires_at,'YYYY-MM-DD HH24:MI:SS TZH:TZM'))",['user_id'=>$userId,'selector'=>$selector,'hash'=>$hash,'expires_at'=>$expiresAt]);
    }

    public function resetToken(string $selector): ?array
    {
        return Database::one('SELECT token_id,user_id,validator_hash FROM password_reset_token WHERE selector=:selector AND used_at IS NULL AND expires_at>SYSTIMESTAMP',['selector'=>$selector]);
    }

    public function consumeReset(int $tokenId,int $userId,string $passwordHash): void
    {
        Database::transaction(function()use($tokenId,$userId,$passwordHash):void{
            Database::execute('UPDATE app_user SET password_hash=:hash,remember_selector=NULL,remember_validator_hash=NULL,remember_expires_at=NULL,updated_at=SYSTIMESTAMP WHERE user_id=:id',['hash'=>$passwordHash,'id'=>$userId],false);
            Database::execute('UPDATE password_reset_token SET used_at=SYSTIMESTAMP WHERE token_id=:id',['id'=>$tokenId],false);
        });
    }

    public function list(int $page, string $search = ''): array
    {
        $start = (($page - 1) * 20) + 1; $end = $page * 20; $q = '%' . strtolower($search) . '%';
        return Database::all("SELECT * FROM (SELECT x.*,ROW_NUMBER() OVER (ORDER BY user_id) rn FROM (SELECT u.user_id,u.user_name,u.email,u.institution,u.rating,u.account_status,RTRIM(MAX(CASE WHEN r.role_code='ADMIN' THEN 'ADMIN,' END)||MAX(CASE WHEN r.role_code='ORGANIZER' THEN 'ORGANIZER,' END)||MAX(CASE WHEN r.role_code='PARTICIPANT' THEN 'PARTICIPANT,' END),',') roles FROM app_user u JOIN user_role ur ON ur.user_id=u.user_id JOIN role r ON r.role_id=ur.role_id WHERE LOWER(u.user_name) LIKE :q OR LOWER(u.email) LIKE :q GROUP BY u.user_id,u.user_name,u.email,u.institution,u.rating,u.account_status) x) WHERE rn BETWEEN :row_start AND :row_end", ['q'=>$q,'row_start'=>$start,'row_end'=>$end]);
    }

    public function setStatus(int $id, string $status): void
    {
        Database::execute("UPDATE app_user SET account_status=:status,updated_at=SYSTIMESTAMP WHERE user_id=:id", compact('status','id'));
    }

    public function assignRole(int $id, string $role): void
    {
        Database::execute("MERGE INTO user_role ur USING (SELECT :id user_id,role_id FROM role WHERE role_code=:role) src ON (ur.user_id=src.user_id AND ur.role_id=src.role_id) WHEN NOT MATCHED THEN INSERT (user_id,role_id) VALUES (src.user_id,src.role_id)", compact('id','role'));
    }

    private function withRoles(array $user): array
    {
        $user['roles'] = array_column(Database::all("SELECT r.role_code FROM role r JOIN user_role ur ON ur.role_id=r.role_id WHERE ur.user_id=:id", ['id'=>$user['user_id']]), 'role_code');
        return $user;
    }
}
