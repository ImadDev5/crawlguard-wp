import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from 'dotenv';

config();

const app = express();
const PORT = process.env.PORT || 3005;

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    service: 'payment-processing',
    status: 'healthy',
    timestamp: new Date().toISOString()
  });
});

// Routes
app.get('/', (req, res) => {
  res.json({
    service: 'payment-processing',
    version: '1.0.0',
    description: 'Arbiter Platform Payment-processing Service'
  });
});

app.listen(PORT, () => {
  console.log(`🚀 Payment Processing service running on port ${PORT}`);
});
