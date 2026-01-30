# Eye Movement Detection Implementation

## Overview
Enhanced eye movement detection system with detailed gaze direction tracking and alert system for the AI-Powered Interview System.

## Features Implemented

### 1. **Gaze Direction Detection**
- **Left/Right Detection**: Detects when eyes look left or right for extended periods
- **Up/Down Detection**: Detects when eyes look up or down
- **Center Detection**: Tracks when eyes are focused on screen
- Uses eye landmark positions relative to eye bounding boxes

### 2. **Rapid Eye Movement Detection**
- Tracks eye movement history over time
- Detects rapid changes in gaze direction (3+ different directions in 5 frames)
- Helps identify suspicious scanning behavior

### 3. **Alert System**
- **Visual Alerts**: Color-coded warnings based on severity
  - 🔴 Red (High): No face, Multiple faces
  - 🟡 Yellow (Medium): Looking away, Face too far, Rapid movement
  - 🟠 Orange (Low): Eye movement left/right/up/down
- **Audio Alerts**: Beep sound for immediate feedback
- **Interviewer Notifications**: Real-time alerts sent via Socket.IO

### 4. **Severity Levels**
- **High**: Critical violations (no face, multiple faces)
- **Medium**: Moderate violations (looking away, rapid movement)
- **Low**: Minor violations (directional eye movements)

## Technical Implementation

### Key Functions

#### `detectGazeDirection(leftEye, rightEye)`
- Calculates eye center position
- Computes eye bounding box
- Normalizes position to -1 to 1 range
- Determines primary gaze direction (left/right/up/down/center)
- Returns confidence score

#### `trackEyeMovement(gazeDirection, timestamp)`
- Maintains movement history (last 4 detections = 2 seconds)
- Detects rapid movement patterns
- Returns movement type

#### `calculateEyeCenter(eyeLandmarks)`
- Calculates center point of eye from landmarks
- Used for gaze direction calculation

#### `calculateEyeBounds(eyeLandmarks)`
- Calculates bounding box of eye
- Used to normalize eye position

### Detection Thresholds

- **Eye Movement Threshold**: 0.15 (15% deviation from center)
- **Alert Trigger**: 4 consecutive frames (2 seconds) of sustained movement
- **Rapid Movement**: 3+ different directions in 5 frames
- **Detection Interval**: 500ms (2 checks per second)

## Alert Types

1. **eye-movement-left**: Eyes looking left for 2+ seconds
2. **eye-movement-right**: Eyes looking right for 2+ seconds
3. **eye-movement-up**: Eyes looking up for 2+ seconds
4. **eye-movement-down**: Eyes looking down for 2+ seconds
5. **rapid-eye-movement**: Rapid eye scanning detected
6. **looking-away**: General looking away (existing)
7. **no-face**: No face detected (existing)
8. **multiple-faces**: Multiple faces detected (existing)
9. **face-too-far**: Face too far from camera (existing)

## Configuration

### Adjusting Sensitivity

Edit `static/js/face-detection.js`:

```javascript
// Line ~15: Eye movement threshold
let eyeMovementThreshold = 0.15; // Lower = more sensitive

// Line ~16: Rapid movement window
let rapidMovementWindow = 5; // Number of frames to check

// In analyzeFaceDetections():
// Change alert trigger threshold (currently 4 frames = 2 seconds)
if (cheatWarnings.eyeMovementLeft > 4) { // Adjust this number
```

### Detection Interval

```javascript
// Line ~193: Detection frequency
}, 500); // Change to 250 for faster, 1000 for slower
```

## Usage

### For Candidates
- Alerts appear automatically when eye movements are detected
- Visual warnings with color-coded severity
- Audio beep for immediate feedback
- Warnings disappear after 3-4 seconds

### For Interviewers
- Real-time alerts in AI Copilot tab
- Color-coded by severity
- Timestamp for each alert
- Alert history (last 20 alerts shown)
- Toast notifications for immediate awareness

## Browser Console Logs

All detections are logged to console:
```
Cheat detection: Eyes looking left - Please look at screen Type: eye-movement-left
```

## Server-Side Logging

Alerts are logged on server with severity:
```
🔴 Cheat detected in room abc123: eye-movement-left - Eyes looking left at 2024-01-30T10:00:00Z (Severity: low)
```

## Future Enhancements

### Potential Additions:
1. **Database Logging**: Create `cheat_logs` table to persist all alerts
2. **Analytics Dashboard**: Show eye movement patterns over time
3. **Configurable Thresholds**: Allow interviewers to adjust sensitivity
4. **Blink Detection**: Detect excessive blinking
5. **Head Pose Estimation**: Combine with head position for better accuracy
6. **Machine Learning**: Train model on normal vs suspicious patterns

## Testing

1. Start interview as candidate
2. Look left for 2+ seconds → Should see orange alert
3. Look right for 2+ seconds → Should see orange alert
4. Rapidly move eyes → Should see yellow "rapid movement" alert
5. Check interviewer view → Should see alerts in AI tab with severity colors

## References

- **face-api.js Documentation**: https://github.com/vladmandic/face-api
- **Eye Aspect Ratio (EAR)**: Standard computer vision technique for eye state detection
- **Gaze Estimation**: Based on eye landmark positions relative to eye bounds

## Files Modified

1. `static/js/face-detection.js` - Enhanced with gaze direction detection
2. `app.py` - Updated cheat detection handler with severity levels
3. `templates/interview.html` - Enhanced interviewer alert display
4. `FACE_DETECTION_SETUP.md` - Updated documentation
