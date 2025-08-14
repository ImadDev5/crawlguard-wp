/**
 * Arbiter Platform - Workflow Engine Service
 * Manages complex content licensing workflows, approvals, and automation
 * Handles business process orchestration and rule-based decision making
 */

import { EventEmitter } from 'events';

interface WorkflowDefinition {
  workflowId: string;
  name: string;
  description: string;
  version: string;
  category: 'licensing' | 'compliance' | 'approval' | 'onboarding' | 'billing' | 'dispute';
  steps: WorkflowStep[];
  triggers: WorkflowTrigger[];
  rules: WorkflowRule[];
  permissions: WorkflowPermission[];
  sla: {
    maxDuration: number; // hours
    escalationRules: EscalationRule[];
  };
  active: boolean;
  createdAt: Date;
  updatedAt: Date;
}

interface WorkflowStep {
  stepId: string;
  name: string;
  type: 'manual' | 'automated' | 'decision' | 'notification' | 'integration';
  description: string;
  assignee: WorkflowAssignee;
  actions: WorkflowAction[];
  conditions: WorkflowCondition[];
  timeouts: {
    soft: number; // hours
    hard: number; // hours
    action: 'escalate' | 'auto_approve' | 'auto_reject' | 'reassign';
  };
  position: {
    x: number;
    y: number;
  };
  nextSteps: string[];
}

interface WorkflowTrigger {
  triggerId: string;
  event: string;
  conditions: WorkflowCondition[];
  action: 'start_workflow' | 'pause_workflow' | 'cancel_workflow';
  parameters: Record<string, any>;
}

interface WorkflowRule {
  ruleId: string;
  name: string;
  condition: string; // JSON logic expression
  action: WorkflowAction;
  priority: number;
  active: boolean;
}

interface WorkflowPermission {
  role: string;
  actions: string[];
  conditions?: string[];
}

interface WorkflowAssignee {
  type: 'user' | 'role' | 'team' | 'system';
  id: string;
  fallback?: WorkflowAssignee;
}

interface WorkflowAction {
  actionId: string;
  type: 'approve' | 'reject' | 'request_info' | 'escalate' | 'notify' | 'update_data' | 'call_api' | 'send_email';
  parameters: Record<string, any>;
  conditions?: WorkflowCondition[];
}

interface WorkflowCondition {
  field: string;
  operator: 'equals' | 'not_equals' | 'greater_than' | 'less_than' | 'contains' | 'exists' | 'in' | 'matches';
  value: any;
  logic?: 'and' | 'or';
}

interface EscalationRule {
  level: number;
  condition: string;
  assignee: WorkflowAssignee;
  notification: NotificationSettings;
}

interface NotificationSettings {
  channels: ('email' | 'sms' | 'slack' | 'teams' | 'webhook')[];
  template: string;
  recipients: string[];
  immediate: boolean;
}

interface WorkflowInstance {
  instanceId: string;
  workflowId: string;
  status: 'running' | 'completed' | 'failed' | 'cancelled' | 'paused';
  currentStep: string;
  data: Record<string, any>;
  history: WorkflowExecution[];
  startedAt: Date;
  completedAt?: Date;
  assignedTo?: string;
  priority: 'low' | 'normal' | 'high' | 'urgent';
  metadata: {
    initiator: string;
    context: Record<string, any>;
    tags: string[];
  };
}

interface WorkflowExecution {
  executionId: string;
  stepId: string;
  action: string;
  performer: string;
  timestamp: Date;
  duration: number; // milliseconds
  input: Record<string, any>;
  output: Record<string, any>;
  status: 'success' | 'failure' | 'pending';
  notes?: string;
}

interface WorkflowTask {
  taskId: string;
  instanceId: string;
  stepId: string;
  assignee: string;
  title: string;
  description: string;
  dueDate: Date;
  priority: 'low' | 'normal' | 'high' | 'urgent';
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  actions: string[];
  data: Record<string, any>;
  createdAt: Date;
  updatedAt: Date;
}

class WorkflowEngine extends EventEmitter {
  private workflows: Map<string, WorkflowDefinition>;
  private instances: Map<string, WorkflowInstance>;
  private tasks: Map<string, WorkflowTask>;
  private activeExecutions: Map<string, NodeJS.Timeout>;

  constructor() {
    super();
    this.workflows = new Map();
    this.instances = new Map();
    this.tasks = new Map();
    this.activeExecutions = new Map();
    
    this.initializeDefaultWorkflows();
  }

  async createWorkflow(definition: Omit<WorkflowDefinition, 'workflowId' | 'createdAt' | 'updatedAt'>): Promise<WorkflowDefinition> {
    const workflowId = this.generateWorkflowId();
    
    const workflow: WorkflowDefinition = {
      workflowId,
      createdAt: new Date(),
      updatedAt: new Date(),
      ...definition
    };

    this.validateWorkflow(workflow);
    this.workflows.set(workflowId, workflow);
    
    this.emit('workflowCreated', { workflow });
    
    return workflow;
  }

  async startWorkflow(workflowId: string, initialData: Record<string, any>, context: {
    initiator: string;
    priority?: 'low' | 'normal' | 'high' | 'urgent';
    tags?: string[];
  }): Promise<WorkflowInstance> {
    const workflow = this.workflows.get(workflowId);
    if (!workflow || !workflow.active) {
      throw new Error(`Workflow ${workflowId} not found or inactive`);
    }

    const instanceId = this.generateInstanceId();
    const startStep = workflow.steps.find(step => 
      !workflow.steps.some(s => s.nextSteps.includes(step.stepId))
    );

    if (!startStep) {
      throw new Error('No start step found in workflow');
    }

    const instance: WorkflowInstance = {
      instanceId,
      workflowId,
      status: 'running',
      currentStep: startStep.stepId,
      data: initialData,
      history: [],
      startedAt: new Date(),
      priority: context.priority || 'normal',
      metadata: {
        initiator: context.initiator,
        context: {},
        tags: context.tags || []
      }
    };

    this.instances.set(instanceId, instance);
    
    // Execute first step
    await this.executeStep(instanceId, startStep.stepId);
    
    this.emit('workflowStarted', { instance });
    
    return instance;
  }

  async executeStep(instanceId: string, stepId: string, input?: Record<string, any>): Promise<void> {
    const instance = this.instances.get(instanceId);
    if (!instance) {
      throw new Error(`Workflow instance ${instanceId} not found`);
    }

    const workflow = this.workflows.get(instance.workflowId);
    if (!workflow) {
      throw new Error(`Workflow ${instance.workflowId} not found`);
    }

    const step = workflow.steps.find(s => s.stepId === stepId);
    if (!step) {
      throw new Error(`Step ${stepId} not found in workflow`);
    }

    const executionId = this.generateExecutionId();
    const startTime = Date.now();

    try {
      // Check conditions
      if (!this.evaluateConditions(step.conditions, instance.data)) {
        throw new Error('Step conditions not met');
      }

      // Create execution record
      const execution: WorkflowExecution = {
        executionId,
        stepId,
        action: 'execute',
        performer: instance.assignedTo || 'system',
        timestamp: new Date(),
        duration: 0,
        input: input || {},
        output: {},
        status: 'pending'
      };

      instance.history.push(execution);

      // Execute based on step type
      let result: Record<string, any> = {};
      
      switch (step.type) {
        case 'automated':
          result = await this.executeAutomatedStep(step, instance, input);
          break;
        case 'manual':
          result = await this.executeManualStep(step, instance, input);
          break;
        case 'decision':
          result = await this.executeDecisionStep(step, instance, input);
          break;
        case 'notification':
          result = await this.executeNotificationStep(step, instance, input);
          break;
        case 'integration':
          result = await this.executeIntegrationStep(step, instance, input);
          break;
        default:
          throw new Error(`Unknown step type: ${step.type}`);
      }

      // Update execution
      execution.duration = Date.now() - startTime;
      execution.output = result;
      execution.status = 'success';

      // Update instance data
      if (result.data) {
        instance.data = { ...instance.data, ...result.data };
      }

      // Determine next steps
      const nextSteps = this.determineNextSteps(step, result, instance.data);
      
      if (nextSteps.length === 0) {
        // Workflow completed
        instance.status = 'completed';
        instance.completedAt = new Date();
        this.emit('workflowCompleted', { instance });
      } else if (nextSteps.length === 1) {
        // Continue to next step
        instance.currentStep = nextSteps[0];
        await this.executeStep(instanceId, nextSteps[0]);
      } else {
        // Multiple next steps - create parallel tasks
        for (const nextStepId of nextSteps) {
          await this.createTask(instanceId, nextStepId);
        }
      }

      this.instances.set(instanceId, instance);
      this.emit('stepCompleted', { instance, step, result });

    } catch (error) {
      // Handle execution error
      const execution = instance.history[instance.history.length - 1];
      execution.duration = Date.now() - startTime;
      execution.status = 'failure';
      execution.output = { error: error.message };

      instance.status = 'failed';
      this.instances.set(instanceId, instance);
      
      this.emit('stepFailed', { instance, step, error });
      this.emit('workflowFailed', { instance, error });
    }
  }

  async completeTask(taskId: string, action: string, data: Record<string, any>, performer: string): Promise<void> {
    const task = this.tasks.get(taskId);
    if (!task) {
      throw new Error(`Task ${taskId} not found`);
    }

    if (task.status !== 'pending' && task.status !== 'in_progress') {
      throw new Error(`Task ${taskId} is not available for completion`);
    }

    // Update task
    task.status = 'completed';
    task.updatedAt = new Date();
    this.tasks.set(taskId, task);

    // Continue workflow
    const instance = this.instances.get(task.instanceId);
    if (instance) {
      instance.assignedTo = performer;
      await this.executeStep(task.instanceId, task.stepId, { action, ...data });
    }

    this.emit('taskCompleted', { task, performer, action, data });
  }

  async pauseWorkflow(instanceId: string): Promise<void> {
    const instance = this.instances.get(instanceId);
    if (!instance) {
      throw new Error(`Workflow instance ${instanceId} not found`);
    }

    instance.status = 'paused';
    this.instances.set(instanceId, instance);
    
    // Cancel any active executions
    const timeout = this.activeExecutions.get(instanceId);
    if (timeout) {
      clearTimeout(timeout);
      this.activeExecutions.delete(instanceId);
    }

    this.emit('workflowPaused', { instance });
  }

  async resumeWorkflow(instanceId: string): Promise<void> {
    const instance = this.instances.get(instanceId);
    if (!instance) {
      throw new Error(`Workflow instance ${instanceId} not found`);
    }

    if (instance.status !== 'paused') {
      throw new Error('Workflow is not paused');
    }

    instance.status = 'running';
    this.instances.set(instanceId, instance);
    
    // Resume from current step
    await this.executeStep(instanceId, instance.currentStep);
    
    this.emit('workflowResumed', { instance });
  }

  async cancelWorkflow(instanceId: string, reason: string): Promise<void> {
    const instance = this.instances.get(instanceId);
    if (!instance) {
      throw new Error(`Workflow instance ${instanceId} not found`);
    }

    instance.status = 'cancelled';
    instance.completedAt = new Date();
    this.instances.set(instanceId, instance);

    // Cancel related tasks
    for (const [taskId, task] of this.tasks) {
      if (task.instanceId === instanceId && task.status === 'pending') {
        task.status = 'cancelled';
        task.updatedAt = new Date();
        this.tasks.set(taskId, task);
      }
    }

    this.emit('workflowCancelled', { instance, reason });
  }

  async getWorkflowInstance(instanceId: string): Promise<WorkflowInstance | null> {
    return this.instances.get(instanceId) || null;
  }

  async getWorkflowTasks(assignee: string, status?: string): Promise<WorkflowTask[]> {
    const tasks: WorkflowTask[] = [];
    
    for (const task of this.tasks.values()) {
      if (task.assignee === assignee) {
        if (!status || task.status === status) {
          tasks.push(task);
        }
      }
    }

    return tasks.sort((a, b) => b.priority.localeCompare(a.priority));
  }

  private async executeAutomatedStep(step: WorkflowStep, instance: WorkflowInstance, input?: Record<string, any>): Promise<Record<string, any>> {
    // Execute automated actions
    const results: Record<string, any> = {};
    
    for (const action of step.actions) {
      switch (action.type) {
        case 'update_data':
          Object.assign(instance.data, action.parameters);
          break;
        case 'call_api':
          // API call logic would go here
          results.apiResponse = { success: true };
          break;
        default:
          console.log(`Automated action: ${action.type}`);
      }
    }

    return { data: results };
  }

  private async executeManualStep(step: WorkflowStep, instance: WorkflowInstance, input?: Record<string, any>): Promise<Record<string, any>> {
    // Create manual task
    await this.createTask(instance.instanceId, step.stepId);
    
    // Return pending status - actual completion happens via completeTask
    return { status: 'pending' };
  }

  private async executeDecisionStep(step: WorkflowStep, instance: WorkflowInstance, input?: Record<string, any>): Promise<Record<string, any>> {
    // Evaluate decision rules
    const workflow = this.workflows.get(instance.workflowId)!;
    
    for (const rule of workflow.rules) {
      if (this.evaluateRuleCondition(rule.condition, instance.data)) {
        return { decision: rule.action, ruleId: rule.ruleId };
      }
    }

    return { decision: 'default' };
  }

  private async executeNotificationStep(step: WorkflowStep, instance: WorkflowInstance, input?: Record<string, any>): Promise<Record<string, any>> {
    // Send notifications
    for (const action of step.actions) {
      if (action.type === 'notify' || action.type === 'send_email') {
        console.log(`Sending notification: ${action.parameters.message}`);
        // Notification logic would go here
      }
    }

    return { notificationsSent: step.actions.length };
  }

  private async executeIntegrationStep(step: WorkflowStep, instance: WorkflowInstance, input?: Record<string, any>): Promise<Record<string, any>> {
    // External system integration
    console.log(`Integration step: ${step.name}`);
    return { integrationComplete: true };
  }

  private async createTask(instanceId: string, stepId: string): Promise<WorkflowTask> {
    const instance = this.instances.get(instanceId)!;
    const workflow = this.workflows.get(instance.workflowId)!;
    const step = workflow.steps.find(s => s.stepId === stepId)!;

    const taskId = this.generateTaskId();
    const task: WorkflowTask = {
      taskId,
      instanceId,
      stepId,
      assignee: this.resolveAssignee(step.assignee),
      title: `${workflow.name} - ${step.name}`,
      description: step.description,
      dueDate: new Date(Date.now() + step.timeouts.soft * 60 * 60 * 1000),
      priority: instance.priority,
      status: 'pending',
      actions: step.actions.map(a => a.type),
      data: instance.data,
      createdAt: new Date(),
      updatedAt: new Date()
    };

    this.tasks.set(taskId, task);
    this.emit('taskCreated', { task });

    // Set timeout
    if (step.timeouts.hard > 0) {
      const timeout = setTimeout(() => {
        this.handleTimeout(taskId);
      }, step.timeouts.hard * 60 * 60 * 1000);
      
      this.activeExecutions.set(taskId, timeout);
    }

    return task;
  }

  private determineNextSteps(step: WorkflowStep, result: Record<string, any>, data: Record<string, any>): string[] {
    // Simple implementation - could be more sophisticated
    return step.nextSteps;
  }

  private evaluateConditions(conditions: WorkflowCondition[], data: Record<string, any>): boolean {
    if (!conditions || conditions.length === 0) return true;
    
    // Simplified condition evaluation
    return conditions.every(condition => {
      const value = data[condition.field];
      switch (condition.operator) {
        case 'equals':
          return value === condition.value;
        case 'exists':
          return value !== undefined;
        default:
          return true;
      }
    });
  }

  private evaluateRuleCondition(condition: string, data: Record<string, any>): boolean {
    // Would use proper JSON logic evaluation in production
    return true;
  }

  private resolveAssignee(assignee: WorkflowAssignee): string {
    // Simple resolution - would be more sophisticated in production
    return assignee.id;
  }

  private handleTimeout(taskId: string): void {
    const task = this.tasks.get(taskId);
    if (task && task.status === 'pending') {
      // Handle timeout based on configuration
      this.emit('taskTimeout', { task });
    }
  }

  private validateWorkflow(workflow: WorkflowDefinition): void {
    if (!workflow.steps || workflow.steps.length === 0) {
      throw new Error('Workflow must have at least one step');
    }

    // Validate step references
    const stepIds = new Set(workflow.steps.map(s => s.stepId));
    for (const step of workflow.steps) {
      for (const nextStepId of step.nextSteps) {
        if (!stepIds.has(nextStepId)) {
          throw new Error(`Invalid next step reference: ${nextStepId}`);
        }
      }
    }
  }

  private initializeDefaultWorkflows(): void {
    // Content licensing approval workflow
    const licensingWorkflow: WorkflowDefinition = {
      workflowId: 'licensing-approval',
      name: 'Content Licensing Approval',
      description: 'Standard approval process for content licensing requests',
      version: '1.0',
      category: 'licensing',
      steps: [
        {
          stepId: 'initial-review',
          name: 'Initial Review',
          type: 'automated',
          description: 'Automated initial review of licensing request',
          assignee: { type: 'system', id: 'system' },
          actions: [
            {
              actionId: 'validate',
              type: 'update_data',
              parameters: { status: 'under_review' }
            }
          ],
          conditions: [],
          timeouts: { soft: 1, hard: 2, action: 'escalate' },
          position: { x: 0, y: 0 },
          nextSteps: ['legal-review']
        },
        {
          stepId: 'legal-review',
          name: 'Legal Review',
          type: 'manual',
          description: 'Legal team reviews licensing terms',
          assignee: { type: 'role', id: 'legal' },
          actions: [
            {
              actionId: 'approve',
              type: 'approve',
              parameters: {}
            },
            {
              actionId: 'reject',
              type: 'reject',
              parameters: {}
            }
          ],
          conditions: [],
          timeouts: { soft: 24, hard: 48, action: 'escalate' },
          position: { x: 1, y: 0 },
          nextSteps: ['final-approval']
        },
        {
          stepId: 'final-approval',
          name: 'Final Approval',
          type: 'manual',
          description: 'Final approval by content owner',
          assignee: { type: 'user', id: 'content-owner' },
          actions: [
            {
              actionId: 'approve',
              type: 'approve',
              parameters: {}
            },
            {
              actionId: 'reject',
              type: 'reject',
              parameters: {}
            }
          ],
          conditions: [],
          timeouts: { soft: 12, hard: 24, action: 'auto_approve' },
          position: { x: 2, y: 0 },
          nextSteps: []
        }
      ],
      triggers: [
        {
          triggerId: 'license-request',
          event: 'license_request_created',
          conditions: [],
          action: 'start_workflow',
          parameters: {}
        }
      ],
      rules: [],
      permissions: [
        {
          role: 'legal',
          actions: ['approve', 'reject', 'request_info']
        },
        {
          role: 'content-owner',
          actions: ['approve', 'reject']
        }
      ],
      sla: {
        maxDuration: 72,
        escalationRules: [
          {
            level: 1,
            condition: 'duration > 48',
            assignee: { type: 'role', id: 'manager' },
            notification: {
              channels: ['email'],
              template: 'sla-escalation',
              recipients: ['manager@company.com'],
              immediate: true
            }
          }
        ]
      },
      active: true,
      createdAt: new Date(),
      updatedAt: new Date()
    };

    this.workflows.set(licensingWorkflow.workflowId, licensingWorkflow);
  }

  private generateWorkflowId(): string {
    return `WF-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateInstanceId(): string {
    return `WI-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateExecutionId(): string {
    return `WE-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateTaskId(): string {
    return `WT-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }
}

export default WorkflowEngine;
export {
  WorkflowDefinition,
  WorkflowInstance,
  WorkflowTask,
  WorkflowStep,
  WorkflowExecution
};
