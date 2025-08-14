// Shared API key storage
// In production, this should be replaced with a database

export interface ApiKeyData {
  key: string;
  createdAt: Date;
  userId?: string;
  active: boolean;
  website?: string;
}

class ApiKeyStore {
  private static instance: ApiKeyStore;
  private keys: Map<string, ApiKeyData>;

  private constructor() {
    this.keys = new Map();
  }

  public static getInstance(): ApiKeyStore {
    if (!ApiKeyStore.instance) {
      ApiKeyStore.instance = new ApiKeyStore();
    }
    return ApiKeyStore.instance;
  }

  public addKey(key: string, data: ApiKeyData): void {
    this.keys.set(key, data);
  }

  public getKey(key: string): ApiKeyData | undefined {
    return this.keys.get(key);
  }

  public validateKey(key: string): boolean {
    const keyData = this.keys.get(key);
    return !!keyData && keyData.active;
  }

  public getAllKeys(): Map<string, ApiKeyData> {
    return this.keys;
  }

  public deleteKey(key: string): boolean {
    return this.keys.delete(key);
  }

  public deactivateKey(key: string): boolean {
    const keyData = this.keys.get(key);
    if (keyData) {
      keyData.active = false;
      return true;
    }
    return false;
  }
}

export const apiKeyStore = ApiKeyStore.getInstance();
