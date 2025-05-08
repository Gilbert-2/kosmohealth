/**
 * Script to download TensorFlow.js hand models for gesture recognition
 * 
 * This script downloads the necessary TensorFlow.js models for hand landmark detection
 * and gesture classification. Run this script with Node.js to download the models.
 */

const fs = require('fs');
const path = require('path');
const https = require('https');
const { execSync } = require('child_process');

// Create directories if they don't exist
const createDirIfNotExists = (dirPath) => {
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
    console.log(`Created directory: ${dirPath}`);
  }
};

// Download file from URL
const downloadFile = (url, destPath) => {
  return new Promise((resolve, reject) => {
    const file = fs.createWriteStream(destPath);
    https.get(url, (response) => {
      response.pipe(file);
      file.on('finish', () => {
        file.close();
        console.log(`Downloaded: ${destPath}`);
        resolve();
      });
    }).on('error', (err) => {
      fs.unlink(destPath, () => {}); // Delete the file on error
      reject(err);
    });
  });
};

// Main function to download models
const downloadModels = async () => {
  try {
    // Base directories
    const baseDir = path.join(__dirname);
    const handLandmarkDir = path.join(baseDir, 'hand_landmark_detection');
    const gestureClassifierDir = path.join(baseDir, 'gesture_classifier');
    
    // Create directories
    createDirIfNotExists(handLandmarkDir);
    createDirIfNotExists(gestureClassifierDir);
    
    // URLs for hand landmark detection model
    const handLandmarkModelUrl = 'https://tfhub.dev/mediapipe/tfjs-model/handpose/1/default/1';
    const handLandmarkFiles = [
      'model.json',
      'group1-shard1of2.bin',
      'group1-shard2of2.bin'
    ];
    
    // URLs for gesture classifier model (custom model)
    const gestureClassifierModelUrl = 'https://storage.googleapis.com/tfjs-models/tfjs/gesture_classifier_v1/model.json';
    const gestureClassifierFiles = [
      'model.json',
      'group1-shard1of1.bin'
    ];
    
    // Download hand landmark detection model files
    console.log('Downloading hand landmark detection model...');
    for (const file of handLandmarkFiles) {
      const url = `${handLandmarkModelUrl}/${file}`;
      const destPath = path.join(handLandmarkDir, file);
      await downloadFile(url, destPath);
    }
    
    // Download gesture classifier model files
    console.log('Downloading gesture classifier model...');
    for (const file of gestureClassifierFiles) {
      const url = `${gestureClassifierModelUrl}/${file}`;
      const destPath = path.join(gestureClassifierDir, file);
      await downloadFile(url, destPath);
    }
    
    console.log('All models downloaded successfully!');
  } catch (error) {
    console.error('Error downloading models:', error);
  }
};

// Run the download function
downloadModels();
