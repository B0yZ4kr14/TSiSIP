-- TSiSIP Development Seed Data
-- Contains only precomputed HA1 hashes; no plaintext passwords.

-- Insert a default tenant (UUID used as fallback for subscriber FK during bootstrap)
INSERT INTO tenants (id, name, sip_domain, enabled)
VALUES ('00000000-0000-0000-0000-000000000000', 'Default Tenant', 'sip.tsisip.local', true)
ON CONFLICT (sip_domain) DO NOTHING;

-- Insert a sample tenant for development
INSERT INTO tenants (id, name, sip_domain, enabled)
VALUES (gen_random_uuid(), 'Dev Tenant', 'dev.tsisip.local', true)
ON CONFLICT (sip_domain) DO NOTHING;

-- Insert dispatcher destinations for development
-- Set 1 = default PBX pool
INSERT INTO dispatcher (setid, destination, state, weight, priority, attrs, description)
VALUES
    (1, 'sip:asterisk-pbx-1:5060', 0, 50, 0, '', 'PBX Node 1'),
    (1, 'sip:asterisk-pbx-2:5060', 0, 50, 0, '', 'PBX Node 2')
ON CONFLICT DO NOTHING;

-- Insert a development subscriber with precomputed HA1 hashes
-- Username: devuser, Domain: dev.tsisip.local, Password: devpass
-- HA1 (MD5):    MD5("devuser:dev.tsisip.local:devpass")
-- HA1-SHA256:   SHA-256("devuser:dev.tsisip.local:devpass")
-- HA1-SHA512/256: SHA-512/256("devuser:dev.tsisip.local:devpass")
INSERT INTO subscriber (username, domain, password, ha1, ha1_sha256, ha1_sha512t256, email_address, rpid, tenant_id, routing_group, enabled)
VALUES (
    'devuser',
    'dev.tsisip.local',
    '',
    '0191796b0c1c9dce49d704d14a2cc2ce',
    '7f59a628f489f2a001cf085bc8d6139db2551141acb778a8ba13e42db3d289a3',
    'd43d292f346d716eacfd4e298fb8a3c598edebb3c8ff4dd838be8b5e95d0594d',
    'dev@tsisip.local',
    NULL,
    (SELECT id FROM tenants WHERE sip_domain = 'dev.tsisip.local' LIMIT 1),
    1,
    true
)
ON CONFLICT DO NOTHING;
