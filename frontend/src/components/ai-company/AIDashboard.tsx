import React, { useState, useEffect } from 'react';

interface AICompanyStats {
  totalSpent: number;
  licensesOwned: number;
  apiCalls: number;
  activeProjects: number;
}

interface License {
  id: string;
  contentTitle: string;
  contentType: 'IMAGE' | 'VIDEO' | 'AUDIO' | 'DOCUMENT';
  creator: string;
  price: number;
  purchaseDate: string;
  usageCount: number;
  maxUsage: number;
  status: 'ACTIVE' | 'EXPIRED' | 'PENDING';
}

const AIDashboard = () => {
  const [stats, setStats] = useState({
    totalSpent: 0,
    licensesOwned: 0,
    apiCalls: 0,
    activeProjects: 0
  });
  
  const [recentLicenses, setRecentLicenses] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Simulate API call
    setTimeout(() => {
      setStats({
        totalSpent: 48750.25,
        licensesOwned: 342,
        apiCalls: 156432,
        activeProjects: 12
      });
      
      setRecentLicenses([
        {
          id: '1',
          contentTitle: 'Urban Photography Collection',
          contentType: 'IMAGE',
          creator: 'Sarah Johnson',
          price: 299.99,
          purchaseDate: '2024-01-15',
          usageCount: 1234,
          maxUsage: 10000,
          status: 'ACTIVE'
        },
        {
          id: '2',
          contentTitle: 'Motion Graphics Templates',
          contentType: 'VIDEO',
          creator: 'Marcus Chen',
          price: 499.99,
          purchaseDate: '2024-01-10',
          usageCount: 876,
          maxUsage: 5000,
          status: 'ACTIVE'
        },
        {
          id: '3',
          contentTitle: 'Scientific Research Papers',
          contentType: 'DOCUMENT',
          creator: 'Dr. Elena Rodriguez',
          price: 1299.99,
          purchaseDate: '2024-01-08',
          usageCount: 456,
          maxUsage: 2000,
          status: 'ACTIVE'
        }
      ]);
      
      setLoading(false);
    }, 1000);
  }, []);

  const StatCard = ({ icon, title, value, change }) => (
    <div className="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
      <div className="flex items-center justify-between mb-4">
        <div className="p-3 bg-indigo-100 rounded-lg">
          {icon}
        </div>
        <span className="text-green-600 text-sm font-medium">{change}</span>
      </div>
      <h3 className="text-gray-600 text-sm font-medium mb-1">{title}</h3>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
    </div>
  );

  const LicenseRow = ({ license }) => (
    <tr className="border-b border-gray-200 hover:bg-gray-50 transition-colors">
      <td className="py-4 px-6">
        <div className="flex items-center">
          <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
            {license.contentType === 'IMAGE' && '🖼️'}
            {license.contentType === 'VIDEO' && '🎥'}
            {license.contentType === 'AUDIO' && '🎵'}
            {license.contentType === 'DOCUMENT' && '📄'}
          </div>
          <div>
            <p className="font-medium text-gray-900">{license.contentTitle}</p>
            <p className="text-sm text-gray-500">by {license.creator}</p>
          </div>
        </div>
      </td>
      <td className="py-4 px-6">
        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
          license.status === 'ACTIVE' ? 'bg-green-100 text-green-800' :
          license.status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' :
          'bg-red-100 text-red-800'
        }`}>
          {license.status}
        </span>
      </td>
      <td className="py-4 px-6 text-gray-900">${license.price.toFixed(2)}</td>
      <td className="py-4 px-6">
        <div className="flex items-center">
          <div className="w-full bg-gray-200 rounded-full h-2 mr-3">
            <div 
              className="bg-indigo-600 h-2 rounded-full" 
              style={{ width: `${(license.usageCount / license.maxUsage) * 100}%` }}
            ></div>
          </div>
          <span className="text-sm text-gray-500">
            {license.usageCount}/{license.maxUsage}
          </span>
        </div>
      </td>
      <td className="py-4 px-6 text-gray-500">
        {new Date(license.purchaseDate).toLocaleDateString()}
      </td>
    </tr>
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">AI Company Dashboard</h1>
          <p className="text-gray-600">Monitor your content licenses and API usage</p>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatCard 
            icon={<span className="text-2xl">💳</span>}
            title="Total Spent"
            value={`$${stats.totalSpent.toLocaleString()}`}
            change="+12.5%"
          />
          <StatCard 
            icon={<span className="text-2xl">📜</span>}
            title="Licenses Owned"
            value={stats.licensesOwned.toString()}
            change="+18.3%"
          />
          <StatCard 
            icon={<span className="text-2xl">🔌</span>}
            title="API Calls"
            value={stats.apiCalls.toLocaleString()}
            change="+34.2%"
          />
          <StatCard 
            icon={<span className="text-2xl">🚀</span>}
            title="Active Projects"
            value={stats.activeProjects.toString()}
            change="+25.0%"
          />
        </div>

        {/* Quick Actions */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <button className="bg-indigo-600 text-white p-6 rounded-xl hover:bg-indigo-700 transition-colors">
            <span className="text-3xl mb-2 block">🛍️</span>
            <h3 className="text-lg font-semibold">Browse Marketplace</h3>
            <p className="text-sm opacity-90">Discover new content for your AI models</p>
          </button>
          
          <button className="bg-green-600 text-white p-6 rounded-xl hover:bg-green-700 transition-colors">
            <span className="text-3xl mb-2 block">📊</span>
            <h3 className="text-lg font-semibold">Usage Analytics</h3>
            <p className="text-sm opacity-90">Track your API usage and performance</p>
          </button>
          
          <button className="bg-purple-600 text-white p-6 rounded-xl hover:bg-purple-700 transition-colors">
            <span className="text-3xl mb-2 block">🔧</span>
            <h3 className="text-lg font-semibold">API Management</h3>
            <p className="text-sm opacity-90">Manage your API keys and integrations</p>
          </button>
        </div>

        {/* Recent Licenses */}
        <div className="bg-white rounded-xl shadow-lg overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900">Recent Licenses</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Content
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Price
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Usage
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Purchase Date
                  </th>
                </tr>
              </thead>
              <tbody>
                {recentLicenses.map((license, index) => (
                  <LicenseRow key={index} license={license} />
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AIDashboard;
