-- Safe production seed: role definitions only. Create an administrator with bootstrap_admin.php.
INSERT INTO role(role_id, role_code, role_name) VALUES
    (1, 'PARTICIPANT', 'Participant'),
    (2, 'ORGANIZER', 'Organizer'),
    (3, 'ADMIN', 'Administrator')
ON CONFLICT (role_id) DO NOTHING;
