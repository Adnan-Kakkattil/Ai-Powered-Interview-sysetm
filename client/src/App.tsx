import { ChangeEvent, FormEvent, useMemo, useState } from 'react';
import './App.css';
import { api } from './lib/api';
import { useInterviewRealtime } from './hooks/useInterviewRealtime';

type Role = 'candidate' | 'interviewer';

const defaultForm = {
  interviewId: 'demo-interview-001',
  participantId: 'user-1',
  displayName: 'Demo User',
  role: 'candidate' as Role,
};

function App() {
  const [form, setForm] = useState(defaultForm);
  const [error, setError] = useState<string | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [roomName, setRoomName] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const identityValue = useMemo(() => {
    if (!roomName || !token) return null;
    return `${form.role}-${form.participantId}`;
  }, [form.participantId, form.role, roomName, token]);

  const realtime = useInterviewRealtime({
    interviewId: form.interviewId,
    identity: identityValue ?? '',
    role: form.role,
    enabled: Boolean(identityValue),
  });

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const response = await api.createJoinToken({
        interviewId: form.interviewId,
        participantId: form.participantId,
        role: form.role,
        displayName: form.displayName,
      });
      setToken(response.token);
      setRoomName(response.roomName);
      // setIdentity(`${form.role}-${form.participantId}`); // This line was removed
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to create join token');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (key: keyof typeof form) => (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setForm((prev) => ({ ...prev, [key]: event.target.value }));
  };

  const hasJoined = Boolean(token && roomName);

  return (
    <div className="app">
      <header className="app__header">
        <h1>SmartHire Realtime Client</h1>
        <p>Connects to the new realtime-service to fetch tokens, join Socket.IO presence, and prep video sessions.</p>
      </header>

      <main className="app__main">
        <section className="card">
          <h2>1. Request LiveKit join token</h2>
          <p className="card__hint">
            Provide an interview ID, participant ID, and role. The client will call <code>/api/interviews/join-token</code> on the realtime service.
          </p>
          <form className="form" onSubmit={handleSubmit}>
            <label className="form__field">
              <span>Interview ID</span>
              <input value={form.interviewId} onChange={handleChange('interviewId')} required />
            </label>
            <label className="form__field">
              <span>Participant ID</span>
              <input value={form.participantId} onChange={handleChange('participantId')} required />
            </label>
            <label className="form__field">
              <span>Display Name</span>
              <input value={form.displayName} onChange={handleChange('displayName')} required />
            </label>
            <label className="form__field">
              <span>Role</span>
              <select value={form.role} onChange={handleChange('role')}>
                <option value="candidate">Candidate</option>
                <option value="interviewer">Interviewer</option>
              </select>
            </label>
            <button type="submit" className="button" disabled={loading}>
              {loading ? 'Requesting token…' : 'Request Join Token'}
            </button>
            {error && <p className="form__error">{error}</p>}
          </form>

          {hasJoined && (
            <div className="token-preview">
              <p>
                <strong>LiveKit Room:</strong> {roomName}
              </p>
              <p>
                <strong>Identity:</strong> {identityValue}
              </p>
              <p className="token-preview__token">
                <strong>Token:</strong>
                <span>{token}</span>
              </p>
              <p className="card__hint">
                Use this token with the LiveKit client SDK to join the media session. For now, we connect to Socket.IO for presence updates.
              </p>
            </div>
          )}
        </section>

        <section className="card">
          <h2>2. Real-time presence & signalling</h2>
          <p className="card__hint">
            As soon as a token is issued, the client enters the <code>/interview</code> namespace and listens for presence events and call readiness.
          </p>

          {!hasJoined ? (
            <p className="form__error">Generate a join token first to connect.</p>
          ) : (
            <div className="presence-panel">
              <div className="presence-panel__status">
                <span className={`badge ${realtime.callReady ? 'badge--ready' : 'badge--waiting'}`}>
                  {realtime.callReady ? 'Both roles present — you can start the interview' : 'Waiting for both parties…'}
                </span>
              </div>
              <h3>Participants</h3>
              <ul className="presence-panel__list">
                {realtime.participants.map((participant) => (
                  <li key={participant.socketId}>
                    <strong>{participant.identity}</strong>
                    <span className="badge badge--light">{participant.role}</span>
                  </li>
                ))}
                {realtime.participants.length === 0 && <li>No participants connected yet.</li>}
              </ul>
            </div>
          )}
        </section>

        <section className="card">
          <h2>3. Next integration steps</h2>
          <ol className="card__list">
            <li>
              Use the returned LiveKit token in your candidate/interviewer views to join the media room via <code>livekit-client</code>.
            </li>
            <li>
              Wire the existing WebRTC UI (or replace with LiveKit React components) to stream audio/video and screen share.
            </li>
            <li>
              Expand Socket.IO events for chat, collaborative editor, eye-tracking metrics, and analytics.
            </li>
          </ol>
        </section>
      </main>
    </div>
  );
}

export default App;
