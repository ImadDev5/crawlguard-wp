import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from 'dotenv';

config();

const app = express();
const PORT = process.env.PORT || 3010;

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    service: 'api-gateway',
    status: 'healthy',
    timestamp: new Date().toISOString()
  });
});

// Routes
app.get('/', (req, res) => {
  res.json({
    service: 'api-gateway',
    version: '1.0.0',
    description: 'Arbiter Platform Api-gateway Service'
  });
});

app.listen(PORT, () => {
  console.log(`🚀 API Gateway service running on port ${PORT}`);
});
