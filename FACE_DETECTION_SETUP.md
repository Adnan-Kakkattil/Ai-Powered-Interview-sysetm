# Face Detection Setup Guide

## Overview
This application uses face-api.js (vladmandic/face-api) for real-time face and eye detection during interviews to detect potential cheating behavior.

## Features Detected
1. **No Face Detected** - Candidate not visible in camera
2. **Multiple Faces** - More than one person detected
3. **Looking Away** - Candidate not looking at screen (eye contact detection)
4. **Face Too Far** - Candidate too far from camera

## Setup Instructions

### 1. Library Already Included
The face-api.js library is loaded from CDN in `templates/interview.html`:
```html
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
```

### 2. Models Loading
Models are automatically loaded from CDN when the interview starts:
- Tiny Face Detector
- Face Landmark 68 Net

### 3. How It Works
- Face detection runs on the candidate's local video stream
- Detections are analyzed every 500ms
- Warnings are shown to candidate if suspicious behavior is detected
- Alerts are sent to interviewer via Socket.IO

### 4. Testing
1. Start an interview as a candidate
2. Allow camera access
3. Wait for "AI Active" status indicator
4. Try looking away, moving too far, or having someone else in frame
5. Check browser console for detection logs

## Troubleshooting

### Models Not Loading
- Check browser console for errors
- Ensure internet connection (models load from CDN)
- Check if face-api.js library loaded successfully

### Detection Not Working
- Verify camera permissions are granted
- Check if video stream is active
- Look for errors in browser console
- Ensure video element has valid dimensions

## Customization

### Adjust Detection Sensitivity
Edit `static/js/face-detection.js`:
- `cheatWarnings` thresholds (lines 10-15)
- Detection interval (line 145, currently 500ms)
- EAR threshold for eye contact (line 78, currently 0.2)

### Add More Detection Types
Extend `analyzeFaceDetections()` function in `static/js/face-detection.js`

