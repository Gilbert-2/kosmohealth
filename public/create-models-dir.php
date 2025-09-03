<?php
// Create models directory if it doesn't exist
$modelsDir = __DIR__ . '/js/face-api-models';

header('Content-Type: application/json');

try {
    if (!file_exists($modelsDir)) {
        if (mkdir($modelsDir, 0755, true)) {
            echo json_encode(['success' => true, 'message' => 'Models directory created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create models directory']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Models directory already exists']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
