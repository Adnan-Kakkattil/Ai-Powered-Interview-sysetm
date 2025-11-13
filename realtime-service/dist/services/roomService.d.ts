type ParticipantRole = 'candidate' | 'interviewer' | 'observer';
type Participant = {
    socketId: string;
    identity: string;
    role: ParticipantRole;
};
type RoomState = {
    interviewId: string;
    roomName: string;
    participants: Map<string, Participant>;
    livekitRoom?: string;
    metadata?: Record<string, unknown>;
};
export declare const roomService: {
    join(interviewId: string, participant: Participant): RoomState;
    leave(interviewId: string, socketId: string): void;
    leaveAll(socketId: string): void;
    listParticipants(interviewId: string): Participant[];
    getRoom(interviewId: string): RoomState | undefined;
    assignLivekitRoom(interviewId: string, roomName?: string): string;
};
export declare const roomHelpers: {
    areBothSidesPresent(interviewId: string): boolean;
};
export {};
//# sourceMappingURL=roomService.d.ts.map