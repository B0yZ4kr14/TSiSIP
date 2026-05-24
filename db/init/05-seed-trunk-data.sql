INSERT INTO sip_trunk_providers (
    name, host, port, transport, auth_username, auth_password_encrypted,
    from_domain, caller_id_prefix, priority, enabled,
    registration_required, max_cps, max_concurrent
) VALUES (
    'TestProvider',
    'sip.provider.test',
    5060,
    'udp',
    'testuser',
    NULL,
    'sip.provider.test',
    '',
    10,
    false,
    false,
    10,
    100
)
ON CONFLICT (name) DO NOTHING;

-- Sample DID mapping to the default tenant
INSERT INTO sip_trunk_did_mappings (
    trunk_provider_id,
    did_number,
    tenant_id,
    dispatcher_setid,
    destination,
    enabled
)
SELECT
    (SELECT id FROM sip_trunk_providers WHERE name = 'TestProvider' LIMIT 1),
    '+15551234567',
    (SELECT id FROM tenants WHERE sip_domain = 'sip.tsisip.local' LIMIT 1),
    1,
    'sip:reception@sip.tsisip.local',
    true
ON CONFLICT (did_number, trunk_provider_id) DO NOTHING;
