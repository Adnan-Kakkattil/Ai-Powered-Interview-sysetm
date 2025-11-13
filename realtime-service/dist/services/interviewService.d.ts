export type JoinRequest = {
    interviewId: string;
    participantId: string;
    role: 'candidate' | 'interviewer';
    displayName: string;
};
export declare const interviewService: {
    registerParticipant({ interviewId, participantId, role, displayName }: JoinRequest): {
        token: Promise<string>;
        roomName: string;
    };
};
//# sourceMappingURL=interviewService.d.ts.map