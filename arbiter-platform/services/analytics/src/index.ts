import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from 'dotenv';

config();

const app = express();
const PORT = process.env.PORT || 3006;

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    service: 'analytics',
    status: 'healthy',
    timestamp: new Date().toISOString()
  });
});

// Routes
app.get('/', (req, res) => {
  res.json({
    service: 'analytics',
    version: '1.0.0',
    description: 'Arbiter Platform Analytics Service'
  });
});

app.listen(PORT, () => {
  console.log(`🚀 Analytics service running on port ${PORT}`);
});
