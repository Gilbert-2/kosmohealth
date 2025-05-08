// This script downloads the face-api.js models
// Run this script in the browser console to download the models

async function downloadFaceApiModels() {
  const modelsUrl = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
  const modelNames = [
    // Face detection
    'tiny_face_detector_model-weights_manifest.json',
    'tiny_face_detector_model-shard1',
    // Face landmark detection
    'face_landmark_68_model-weights_manifest.json',
    'face_landmark_68_model-shard1',
    // Face recognition
    'face_recognition_model-weights_manifest.json',
    'face_recognition_model-shard1',
    'face_recognition_model-shard2',
    // Face expression recognition
    'face_expression_model-weights_manifest.json',
    'face_expression_model-shard1'
  ];

  // Create directory for models
  const modelsDir = '/js/face-api-models';
  
  // Download each model
  for (const modelName of modelNames) {
    const url = `${modelsUrl}/${modelName}`;
    const response = await fetch(url);
    const blob = await response.blob();
    
    // Create a download link
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = modelName;
    link.click();
    
    console.log(`Downloaded: ${modelName}`);
  }
  
  console.log('All models downloaded successfully!');
  console.log('Please move the downloaded files to the public/js/face-api-models directory.');
}

// Run the download function
downloadFaceApiModels();
