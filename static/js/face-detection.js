// Face and Eye Detection for Cheat Detection
// Using face-api.js library (vladmandic/face-api)

let faceDetectionModel = null;
let isDetecting = false;
let detectionInterval = null;
let detectionCanvas = null;
let detectionContext = null;
let cheatWarnings = {
    noFace: 0,
    multipleFaces: 0,
    lookingAway: 0,
    faceTooFar: 0,
    eyeMovementLeft: 0,
    eyeMovementRight: 0,
    eyeMovementUp: 0,
    eyeMovementDown: 0,
    rapidEyeMovement: 0
};

// Eye movement tracking
let eyeMovementHistory = [];
let lastEyePosition = { left: null, right: null };
let eyeMovementThreshold = 0.15; // Threshold for detecting significant eye movement
let rapidMovementWindow = 5; // Number of frames to check for rapid movement

// Initialize face detection models
async function loadFaceDetectionModels() {
    try {
        console.log('Loading face detection models...');
        
        // Check if faceapi is available
        if (typeof faceapi === 'undefined') {
            console.error('face-api.js library not loaded');
            return false;
        }
        
        // Load models from CDN (vladmandic/face-api)
        const modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        
        await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
        await faceapi.nets.faceLandmark68Net.loadFromUri(modelPath);
        
        console.log('Face detection models loaded successfully');
        return true;
    } catch (error) {
        console.error('Error loading face detection models:', error);
        return false;
    }
}

// Calculate eye aspect ratio (EAR) to detect if eyes are closed or looking away
function calculateEAR(eyeLandmarks) {
    // Eye landmarks: 6 points for each eye
    if (!eyeLandmarks || eyeLandmarks.length < 6) return 0.3; // Default open
    
    // Get eye points
    const points = eyeLandmarks.map(p => ({ x: p.x, y: p.y }));
    
    // Calculate vertical distances (top to bottom)
    const vertical1 = Math.abs(points[1].y - points[5].y);
    const vertical2 = Math.abs(points[2].y - points[4].y);
    
    // Calculate horizontal distance (left to right)
    const horizontal = Math.abs(points[0].x - points[3].x);
    
    if (horizontal === 0) return 0.3;
    
    // EAR formula - lower value means eyes are more closed
    const ear = (vertical1 + vertical2) / (2.0 * horizontal);
    return ear;
}

// Calculate eye center position (for gaze direction)
function calculateEyeCenter(eyeLandmarks) {
    if (!eyeLandmarks || eyeLandmarks.length < 6) return null;
    
    const points = eyeLandmarks.map(p => ({ x: p.x, y: p.y }));
    
    // Calculate center as average of all points
    const centerX = points.reduce((sum, p) => sum + p.x, 0) / points.length;
    const centerY = points.reduce((sum, p) => sum + p.y, 0) / points.length;
    
    return { x: centerX, y: centerY };
}

// Calculate eye bounding box
function calculateEyeBounds(eyeLandmarks) {
    if (!eyeLandmarks || eyeLandmarks.length < 6) return null;
    
    const points = eyeLandmarks.map(p => ({ x: p.x, y: p.y }));
    
    const minX = Math.min(...points.map(p => p.x));
    const maxX = Math.max(...points.map(p => p.x));
    const minY = Math.min(...points.map(p => p.y));
    const maxY = Math.max(...points.map(p => p.y));
    
    return {
        minX, maxX, minY, maxY,
        width: maxX - minX,
        height: maxY - minY,
        centerX: (minX + maxX) / 2,
        centerY: (minY + maxY) / 2
    };
}

// Detect gaze direction based on eye position relative to eye bounds
function detectGazeDirection(leftEye, rightEye) {
    if (!leftEye || !rightEye || leftEye.length < 6 || rightEye.length < 6) {
        return { direction: 'center', confidence: 0 };
    }
    
    const leftBounds = calculateEyeBounds(leftEye);
    const rightBounds = calculateEyeBounds(rightEye);
    
    if (!leftBounds || !rightBounds) {
        return { direction: 'center', confidence: 0 };
    }
    
    // Calculate relative position of eye center within bounds
    // Normalize to -1 to 1 range
    const leftCenter = calculateEyeCenter(leftEye);
    const rightCenter = calculateEyeCenter(rightEye);
    
    if (!leftCenter || !rightCenter) {
        return { direction: 'center', confidence: 0 };
    }
    
    // Horizontal gaze (left/right)
    const leftHorizontal = ((leftCenter.x - leftBounds.centerX) / (leftBounds.width / 2));
    const rightHorizontal = ((rightCenter.x - rightBounds.centerX) / (rightBounds.width / 2));
    const avgHorizontal = (leftHorizontal + rightHorizontal) / 2;
    
    // Vertical gaze (up/down)
    const leftVertical = ((leftCenter.y - leftBounds.centerY) / (leftBounds.height / 2));
    const rightVertical = ((rightCenter.y - rightBounds.centerY) / (rightBounds.height / 2));
    const avgVertical = (leftVertical + rightVertical) / 2;
    
    // Determine primary direction
    const horizontalAbs = Math.abs(avgHorizontal);
    const verticalAbs = Math.abs(avgVertical);
    
    let direction = 'center';
    let confidence = 0;
    
    if (horizontalAbs > eyeMovementThreshold || verticalAbs > eyeMovementThreshold) {
        if (horizontalAbs > verticalAbs) {
            direction = avgHorizontal > 0 ? 'right' : 'left';
            confidence = Math.min(horizontalAbs, 1.0);
        } else {
            direction = avgVertical > 0 ? 'down' : 'up';
            confidence = Math.min(verticalAbs, 1.0);
        }
    }
    
    return { direction, confidence, horizontal: avgHorizontal, vertical: avgVertical };
}

// Track eye movement patterns
function trackEyeMovement(gazeDirection, timestamp) {
    // Add to history
    eyeMovementHistory.push({
        direction: gazeDirection.direction,
        confidence: gazeDirection.confidence,
        timestamp: timestamp || Date.now()
    });
    
    // Keep only recent history (last 2 seconds at 500ms intervals = 4 entries)
    if (eyeMovementHistory.length > 4) {
        eyeMovementHistory.shift();
    }
    
    // Detect rapid eye movement
    if (eyeMovementHistory.length >= rapidMovementWindow) {
        const recentMovements = eyeMovementHistory.slice(-rapidMovementWindow);
        const uniqueDirections = new Set(recentMovements.map(m => m.direction));
        
        // If multiple different directions in short time, it's rapid movement
        if (uniqueDirections.size >= 3 && !uniqueDirections.has('center')) {
            return 'rapid';
        }
    }
    
    return gazeDirection.direction;
}

// Detect if person is looking at screen
function isLookingAtScreen(faceLandmarks) {
    if (!faceLandmarks) return false;
    
    try {
        // Get eye landmarks - face-api provides leftEye and rightEye
        const leftEye = faceLandmarks.leftEye || [];
        const rightEye = faceLandmarks.rightEye || [];
        
        if (leftEye.length < 6 || rightEye.length < 6) return true; // Assume looking if can't detect
        
        // Calculate EAR for both eyes
        const leftEAR = calculateEAR(leftEye);
        const rightEAR = calculateEAR(rightEye);
        
        // Average EAR
        const avgEAR = (leftEAR + rightEAR) / 2.0;
        
        // EAR threshold (eyes open and looking forward)
        // Lower EAR = eyes closed or looking away
        // Threshold of 0.2 means eyes are reasonably open
        return avgEAR > 0.2;
    } catch (error) {
        console.error('Error checking eye contact:', error);
        return true; // Default to true to avoid false positives
    }
}

// Detect face position (too close or too far)
function getFaceDistance(face) {
    // Use bounding box area as proxy for distance
    const area = face.detection.box.width * face.detection.box.height;
    return area;
}

// Start face detection on video stream
async function startFaceDetection(videoElement) {
    if (!videoElement || !videoElement.videoWidth) {
        console.error('Video element not ready');
        return;
    }
    
    if (isDetecting) {
        console.log('Face detection already running');
        return;
    }
    
    // Check if faceapi is available
    if (typeof faceapi === 'undefined') {
        console.error('face-api.js not loaded');
        return;
    }
    
    // Check if models are loaded
    if (!faceDetectionModel) {
        const loaded = await loadFaceDetectionModels();
        if (!loaded) {
            console.error('Failed to load face detection models');
            return;
        }
        faceDetectionModel = true;
    }
    
    // Setup canvas for drawing detections
    const canvas = document.getElementById('faceDetectionCanvas');
    if (canvas) {
        detectionCanvas = canvas;
        detectionContext = canvas.getContext('2d');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
    }
    
    isDetecting = true;
    console.log('Starting face detection...');
    
    // Detection options
    const detectionOptions = new faceapi.TinyFaceDetectorOptions({
        inputSize: 320,
        scoreThreshold: 0.5
    });
    
    // Start detection loop
    detectionInterval = setInterval(async () => {
        if (!videoElement || !videoElement.videoWidth || !videoElement.videoHeight) return;
        
        try {
            // Detect faces with landmarks
            const detections = await faceapi
                .detectAllFaces(videoElement, detectionOptions)
                .withFaceLandmarks();
            
            // Draw detections on canvas
            if (detectionCanvas && detectionContext) {
                detectionContext.clearRect(0, 0, detectionCanvas.width, detectionCanvas.height);
                if (detections.length > 0) {
                    // Draw face boxes
                    detections.forEach(detection => {
                        const box = detection.detection.box;
                        detectionContext.strokeStyle = '#00ff00';
                        detectionContext.lineWidth = 2;
                        detectionContext.strokeRect(box.x, box.y, box.width, box.height);
                    });
                    
                    // Draw landmarks if available
                    detections.forEach(detection => {
                        if (detection.landmarks) {
                            const landmarks = detection.landmarks.positions || 
                                            (detection.landmarks.leftEye ? detection.landmarks.leftEye.concat(detection.landmarks.rightEye) : []);
                            detectionContext.fillStyle = '#ff0000';
                            landmarks.forEach(point => {
                                if (point && point.x !== undefined && point.y !== undefined) {
                                    detectionContext.beginPath();
                                    detectionContext.arc(point.x, point.y, 2, 0, 2 * Math.PI);
                                    detectionContext.fill();
                                }
                            });
                        }
                    });
                }
            }
            
            // Analyze detections
            analyzeFaceDetections(detections, videoElement);
            
            // Update status indicator
            updateFaceDetectionStatus(detections);
            
        } catch (error) {
            console.error('Error during face detection:', error);
        }
    }, 500); // Check every 500ms
}

// Update face detection status indicator
function updateFaceDetectionStatus(detections) {
    const statusElement = document.getElementById('faceDetectionStatus');
    if (!statusElement) return;
    
    if (detections.length === 0) {
        statusElement.innerHTML = `
            <span class="bg-black/60 text-red-400 text-[10px] px-1.5 py-0.5 rounded border border-red-500/30">
                <i class="fa-solid fa-user-slash mr-1"></i> No Face
            </span>
        `;
    } else if (detections.length === 1) {
        statusElement.innerHTML = `
            <span class="bg-black/60 text-green-400 text-[10px] px-1.5 py-0.5 rounded border border-green-500/30">
                <i class="fa-solid fa-check-circle mr-1"></i> Monitoring
            </span>
        `;
    } else {
        statusElement.innerHTML = `
            <span class="bg-black/60 text-yellow-400 text-[10px] px-1.5 py-0.5 rounded border border-yellow-500/30">
                <i class="fa-solid fa-users mr-1"></i> Multiple Faces
            </span>
        `;
    }
}

// Analyze face detections for cheating behavior
function analyzeFaceDetections(detections, videoElement) {
    const videoWidth = videoElement.videoWidth;
    const videoHeight = videoElement.videoHeight;
    
    // Reset warnings if face is detected properly
    if (detections.length === 1) {
        const face = detections[0];
        const landmarks = face.landmarks;
        
        // Get eye landmarks
        const leftEye = landmarks.leftEye || [];
        const rightEye = landmarks.rightEye || [];
        
        // Detect gaze direction
        const gazeDirection = detectGazeDirection(leftEye, rightEye);
        const movementType = trackEyeMovement(gazeDirection);
        
        // Check if looking at screen
        const lookingAtScreen = isLookingAtScreen(landmarks);
        
        // Check face distance
        const faceArea = getFaceDistance(face);
        const videoArea = videoWidth * videoHeight;
        const faceRatio = faceArea / videoArea;
        
        // Face too far (less than 5% of video area)
        if (faceRatio < 0.05) {
            cheatWarnings.faceTooFar++;
            if (cheatWarnings.faceTooFar > 3) {
                triggerCheatWarning('Face too far from camera', 'face-too-far');
            }
        } else {
            cheatWarnings.faceTooFar = 0;
        }
        
        // Not looking at screen (general)
        if (!lookingAtScreen) {
            cheatWarnings.lookingAway++;
            if (cheatWarnings.lookingAway > 5) {
                triggerCheatWarning('Not looking at screen', 'looking-away');
            }
        } else {
            cheatWarnings.lookingAway = 0;
        }
        
        // Specific eye movement detection
        if (gazeDirection.direction !== 'center' && gazeDirection.confidence > eyeMovementThreshold) {
            // Detect specific directions
            if (gazeDirection.direction === 'left') {
                cheatWarnings.eyeMovementLeft++;
                if (cheatWarnings.eyeMovementLeft > 4) { // 2 seconds
                    triggerCheatWarning('Eyes looking left - Please look at screen', 'eye-movement-left');
                    cheatWarnings.eyeMovementLeft = 0; // Reset after alert
                }
            } else if (gazeDirection.direction === 'right') {
                cheatWarnings.eyeMovementRight++;
                if (cheatWarnings.eyeMovementRight > 4) {
                    triggerCheatWarning('Eyes looking right - Please look at screen', 'eye-movement-right');
                    cheatWarnings.eyeMovementRight = 0;
                }
            } else if (gazeDirection.direction === 'up') {
                cheatWarnings.eyeMovementUp++;
                if (cheatWarnings.eyeMovementUp > 4) {
                    triggerCheatWarning('Eyes looking up - Please look at screen', 'eye-movement-up');
                    cheatWarnings.eyeMovementUp = 0;
                }
            } else if (gazeDirection.direction === 'down') {
                cheatWarnings.eyeMovementDown++;
                if (cheatWarnings.eyeMovementDown > 4) {
                    triggerCheatWarning('Eyes looking down - Please look at screen', 'eye-movement-down');
                    cheatWarnings.eyeMovementDown = 0;
                }
            }
        } else {
            // Reset counters when looking center
            cheatWarnings.eyeMovementLeft = 0;
            cheatWarnings.eyeMovementRight = 0;
            cheatWarnings.eyeMovementUp = 0;
            cheatWarnings.eyeMovementDown = 0;
        }
        
        // Rapid eye movement detection
        if (movementType === 'rapid') {
            cheatWarnings.rapidEyeMovement++;
            if (cheatWarnings.rapidEyeMovement > 2) {
                triggerCheatWarning('Rapid eye movement detected - Please maintain focus', 'rapid-eye-movement');
                cheatWarnings.rapidEyeMovement = 0;
            }
        } else {
            cheatWarnings.rapidEyeMovement = 0;
        }
        
        // Update last eye position for tracking
        lastEyePosition.left = calculateEyeCenter(leftEye);
        lastEyePosition.right = calculateEyeCenter(rightEye);
        
        // Reset other warnings
        cheatWarnings.noFace = 0;
        cheatWarnings.multipleFaces = 0;
        
    } else if (detections.length === 0) {
        // No face detected
        cheatWarnings.noFace++;
        if (cheatWarnings.noFace > 10) { // 5 seconds of no face (10 * 500ms)
            triggerCheatWarning('No face detected', 'no-face');
        }
    } else if (detections.length > 1) {
        // Multiple faces detected
        cheatWarnings.multipleFaces++;
        if (cheatWarnings.multipleFaces > 3) {
            triggerCheatWarning('Multiple faces detected', 'multiple-faces');
        }
    }
}

// Trigger cheat warning
function triggerCheatWarning(message, type) {
    console.warn('Cheat detection:', message, 'Type:', type);
    
    // Show visual warning
    showCheatWarning(message, type);
    
    // Play alert sound (if available)
    playAlertSound();
    
    // Emit warning to server (for interviewer to see)
    if (typeof socket !== 'undefined' && socket && typeof ROOM_ID !== 'undefined') {
        socket.emit('cheat-detected', {
            room: ROOM_ID,
            type: type,
            message: message,
            timestamp: new Date().toISOString(),
            severity: getSeverityLevel(type)
        });
    }
    
    // Reset warning counter after showing (only for specific types)
    if (type === 'no-face') cheatWarnings.noFace = 0;
    if (type === 'multiple-faces') cheatWarnings.multipleFaces = 0;
    if (type === 'looking-away') cheatWarnings.lookingAway = 0;
    if (type === 'face-too-far') cheatWarnings.faceTooFar = 0;
    // Eye movement warnings are reset in analyzeFaceDetections
}

// Get severity level for different alert types
function getSeverityLevel(type) {
    const severityMap = {
        'no-face': 'high',
        'multiple-faces': 'high',
        'rapid-eye-movement': 'medium',
        'eye-movement-left': 'low',
        'eye-movement-right': 'low',
        'eye-movement-up': 'low',
        'eye-movement-down': 'low',
        'looking-away': 'medium',
        'face-too-far': 'medium'
    };
    return severityMap[type] || 'low';
}

// Play alert sound
function playAlertSound() {
    try {
        // Create audio context for beep sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800; // Beep frequency
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    } catch (error) {
        console.log('Audio alert not available:', error);
    }
}

// Show visual warning on screen
function showCheatWarning(message, type) {
    // Remove existing warnings
    const existingWarning = document.getElementById('cheat-warning');
    if (existingWarning) {
        existingWarning.remove();
    }
    
    // Determine warning color based on severity
    const severity = getSeverityLevel(type);
    let bgColor = 'bg-red-600';
    let icon = 'fa-exclamation-triangle';
    
    if (severity === 'high') {
        bgColor = 'bg-red-700';
        icon = 'fa-exclamation-circle';
    } else if (severity === 'medium') {
        bgColor = 'bg-yellow-600';
        icon = 'fa-exclamation-triangle';
    } else {
        bgColor = 'bg-orange-600';
        icon = 'fa-eye';
    }
    
    // Create warning element
    const warning = document.createElement('div');
    warning.id = 'cheat-warning';
    warning.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 animate-pulse`;
    warning.innerHTML = `
        <i class="fa-solid ${icon} text-xl"></i>
        <span class="font-bold">${message}</span>
    `;
    
    document.body.appendChild(warning);
    
    // Remove warning after 4 seconds (longer for eye movement alerts)
    const displayTime = type.includes('eye-movement') ? 4000 : 3000;
    setTimeout(() => {
        if (warning.parentNode) {
            warning.style.animation = 'fadeOut 0.5s ease-out';
            warning.style.opacity = '0';
            setTimeout(() => {
                if (warning.parentNode) {
                    warning.remove();
                }
            }, 500);
        }
    }, displayTime);
}

// Stop face detection
function stopFaceDetection() {
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }
    isDetecting = false;
    console.log('Face detection stopped');
}

// Initialize when video is ready
function initializeFaceDetection() {
    const localVideo = document.getElementById('localVideo');
    
    if (localVideo && localVideo.srcObject) {
        localVideo.addEventListener('loadedmetadata', () => {
            startFaceDetection(localVideo);
        });
        
        // Also try immediately if video is already loaded
        if (localVideo.readyState >= 2) {
            startFaceDetection(localVideo);
        }
    }
}

// Export functions for global access
window.faceDetection = {
    start: startFaceDetection,
    stop: stopFaceDetection,
    initialize: initializeFaceDetection
};

