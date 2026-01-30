# Face Detection Setup Guide

## Overview
This application uses face-api.js (vladmandic/face-api) for real-time face and eye detection during interviews to detect potential cheating behavior.

## Features Detected
1. **No Face Detected** - Candidate not visible in camera (High Severity)
2. **Multiple Faces** - More than one person detected (High Severity)
3. **Looking Away** - Candidate not looking at screen (Medium Severity)
4. **Face Too Far** - Candidate too far from camera (Medium Severity)
5. **Eye Movement Left** - Eyes looking left for extended period (Low Severity)
6. **Eye Movement Right** - Eyes looking right for extended period (Low Severity)
7. **Eye Movement Up** - Eyes looking up for extended period (Low Severity)
8. **Eye Movement Down** - Eyes looking down for extended period (Low Severity)
9. **Rapid Eye Movement** - Rapid eye movements detected (Medium Severity)

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
- Eye gaze direction is calculated using eye landmarks and position relative to eye bounds
- Warnings are shown to candidate if suspicious behavior is detected
- Visual alerts with color-coded severity (Red=High, Yellow=Medium, Orange=Low)
- Audio beep alerts for immediate feedback
- Alerts are sent to interviewer via Socket.IO with severity levels
- Eye movement history is tracked to detect patterns (rapid movements, sustained looking away)

### 4. Testing
1. Start an interview as a candidate
2. Allow camera access
3. Wait for "AI Active" status indicator
4. Test different scenarios:
   - Look left/right for 2+ seconds → Should trigger "Eyes looking left/right" alert
   - Look up/down for 2+ seconds → Should trigger "Eyes looking up/down" alert
   - Rapidly move eyes in different directions → Should trigger "Rapid eye movement" alert
   - Look away from screen → Should trigger "Not looking at screen" alert
   - Move too far from camera → Should trigger "Face too far" alert
   - Have someone else in frame → Should trigger "Multiple faces" alert
5. Check browser console for detection logs
6. Interviewer should see alerts in the AI tab with severity indicators

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
- `eyeMovementThreshold` (line ~15): Threshold for detecting significant eye movement (default: 0.15)
  - Lower = more sensitive, Higher = less sensitive
- `rapidMovementWindow` (line ~16): Number of frames to check for rapid movement (default: 5)
- Detection interval (line ~193, currently 500ms)
- EAR threshold for eye contact (line ~84, currently 0.2)
- Warning thresholds in `analyzeFaceDetections()`:
  - Eye movement alerts: > 4 frames (2 seconds)
  - Rapid movement: > 2 occurrences
  - Looking away: > 5 frames (2.5 seconds)

### Eye Movement Detection Details
- **Gaze Direction Calculation**: Uses eye center position relative to eye bounding box
- **Normalized Coordinates**: Eye position normalized to -1 to 1 range
- **Direction Detection**: Compares horizontal and vertical offsets to determine primary gaze direction
- **Movement Tracking**: Maintains history of last 4 detections (2 seconds) to detect patterns
- **Rapid Movement**: Detects when 3+ different directions occur within 5 frames

### Alert Types and Severity
- **High Severity** (Red): No face, Multiple faces
- **Medium Severity** (Yellow): Looking away, Face too far, Rapid eye movement
- **Low Severity** (Orange): Eye movement left/right/up/down

### Add More Detection Types
Extend `analyzeFaceDetections()` function in `static/js/face-detection.js` and add new types to `cheatWarnings` object.


