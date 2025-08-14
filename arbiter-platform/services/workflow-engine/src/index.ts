import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from 'dotenv';

config();

const app = express();
const PORT = process.env.PORT || 3004;

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    service: 'workflow-engine',
    status: 'healthy',
    timestamp: new Date().toISOString()
  });
});

// Routes
app.get('/', (req, res) => {
  res.json({
    service: 'workflow-engine',
    version: '1.0.0',
    description: 'Arbiter Platform Workflow-engine Service'
  });
});

app.listen(PORT, () => {
  console.log(`🚀 Workflow Engine service running on port ${PORT}`);
});
