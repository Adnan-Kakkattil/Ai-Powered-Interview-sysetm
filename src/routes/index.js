const { Router } = require('express');
const authRoutes = require('./auth.routes');
const adminRoutes = require('./admin.routes');
const candidateRoutes = require('./candidate.routes');
const interviewRoutes = require('./interview.routes');

const router = Router();

router.get('/', (req, res) => {
  res.json({
    message: 'Smart Interview API',
    version: '1.0.0',
  });
});

router.use('/auth', authRoutes);
router.use('/admin', adminRoutes);
router.use('/candidates', candidateRoutes);
router.use('/interviews', interviewRoutes);

module.exports = router;
