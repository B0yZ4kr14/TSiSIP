<?php
/**
 * TSiSIP Admin API — Health Check Endpoint
 */
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
