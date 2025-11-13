import request from 'supertest';
import express from 'express';
import interviewRoutes from '../src/routes/interviewRoutes';

const app = express();
app.use(express.json());
app.use('/api/interviews', interviewRoutes);

describe('Interview routes', () => {
  it('returns 400 when required fields missing', async () => {
    const response = await request(app).post('/api/interviews/join-token').send({});
    expect(response.status).toBe(400);
  });

  it('returns token when payload valid', async () => {
    const response = await request(app)
      .post('/api/interviews/join-token')
      .send({
        interviewId: 'int-123',
        participantId: 'cand-1',
        role: 'candidate',
        displayName: 'Alice Candidate',
      });

    expect(response.status).toBe(201);
    expect(response.body).toHaveProperty('token');
    expect(response.body).toHaveProperty('roomName');
  });
});

