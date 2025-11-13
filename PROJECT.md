The project involves developing a Smart Interview Management System that streamlines and enhances the online interview process by integrating real-time video, eye-tracking, and coding assessment features. Here's a detailed breakdown of the system based on the abstract provided:
Core Features and Components
User Roles

    Candidates: Can join live video interviews, use embedded coding editors, and have their attentiveness monitored.

    Admins: Manage the entire interview process through dedicated modules.

Candidate-Side Functionalities

    Live Video Interviews: Real-time video communication between candidate and interviewer.

    Embedded Coding Editor: A live, interactive code editor where candidates can write and execute code during the interview.

    Eye-Tracking Monitoring: Utilizes web-based tools like MediaPipe or WebGazer.js to track candidate attentiveness, providing behavioral insights.

Admin-Side Modules

    Interview Page: Interface for conducting live interviews, including coding and attentiveness monitoring.

    Lot Selection Module: Enables admins to handle large pools of candidates by selecting a limited number for interviews, streamlining candidate management.

    Notification Module: Automates sending interview invitations and reminders, including scheduled date and time, to selected candidates.

System Workflow

    Candidate Pool Management: Admins upload and manage a large pool of candidates.

    Lot Selection: Admins select candidates for interviews using the lot selection module.

    Invitation Dispatch: Notifications with scheduled interview details are automatically sent to selected candidates.

    Interview Session:

        Candidates join a live video call.

        Coding tasks are assigned and solved in the embedded editor.

        Eye-tracking monitors candidate attentiveness in real-time.

    Evaluation: Interviewers assess both technical performance (code quality) and behavioral cues (eye-tracking data).

Technologies and Implementation Ideas

    Frontend:

        Web-based interface built with React.js or Vue.js for candidate and admin portals.

        Integration of WebRTC for real-time video streaming.

        Embedded coding editor using CodeMirror or Monaco Editor.

        Eye-tracking integration via MediaPipe or WebGazer.js.

    Backend:

        Node.js or Python (Django/Flask) to manage sessions and user data.

        WebSocket or Socket.io for real-time communication.

        Notification system (email/SMS) using APIs like Twilio or SendGrid.

    Database:

        SQL (PostgreSQL/MySQL) or NoSQL (MongoDB) to store candidate data, interview schedules, and session logs.

    Additional Features:

        Secure authentication and authorization.

        Data encryption for privacy-sensitive information.

        Recording and logging of sessions for later review.

Development Approach

    Phase 1: Design UI/UX for candidate and admin interfaces.

    Phase 2: Implement core functionalities like live video and embedded coding editor.

    Phase 3: Integrate eye-tracking technology.

    Phase 4: Develop candidate management and notification modules.

    Phase 5: Testing, security hardening, and deployment.
