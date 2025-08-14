import React, { useState, useEffect } from 'react';

interface CreatorStats {
  totalRevenue: number;
  contentCount: number;
  viewCount: number;
  licensesSold: number;
}

interface Content {
  id: string;
  title: string;
  type: 'IMAGE' | 'VIDEO' | 'AUDIO' | 'DOCUMENT';
  category: string;
  revenue: number;
  views: number;
  licenses: number;
  uploadDate: string;
  status: 'ACTIVE' | 'PENDING' | 'REJECTED';
}

const CreatorDashboard = () => {
  const [stats, setStats] = useState({
    totalRevenue: 0,
    contentCount: 0,
    viewCount: 0,
    licensesSold: 0
  });
  
  const [recentContent, setRecentContent] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Simulate API call
    setTimeout(() => {
      setStats({
        totalRevenue: 12543.67,
        contentCount: 156,
        viewCount: 89432,
        licensesSold: 1247
      });
      
      setRecentContent([
        {
          id: '1',
          title: 'Urban Photography Collection',
          type: 'IMAGE',
          category: 'Photography',
          revenue: 2534.50,
          views: 1234,
          licenses: 45,
          uploadDate: '2024-01-15',
          status: 'ACTIVE'
        },
        {
          id: '2',
          title: 'Motion Graphics Templates',
          type: 'VIDEO',
          category: 'Animation',
          revenue: 1876.25,
          views: 987,
          licenses: 32,
          uploadDate: '2024-01-10',
          status: 'ACTIVE'
        },
        {
          id: '3',
          title: 'Podcast Background Music',
          type: 'AUDIO',
          category: 'Music',
          revenue: 945.75,
          views: 654,
          licenses: 18,
          uploadDate: '2024-01-08',
          status: 'PENDING'
        }
      ]);
      
      setLoading(false);
    }, 1000);
  }, []);

  const StatCard = ({ icon, title, value, change }) => (
    <div className="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
      <div className="flex items-center justify-between mb-4">
        <div className="p-3 bg-blue-100 rounded-lg">
          {icon}
        </div>
        <span className="text-green-600 text-sm font-medium">{change}</span>
      </div>
      <h3 className="text-gray-600 text-sm font-medium mb-1">{title}</h3>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
    </div>
  );

  const ContentRow = ({ content }) => (
    <tr className="border-b border-gray-200 hover:bg-gray-50 transition-colors">
      <td className="py-4 px-6">
        <div className="flex items-center">
          <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
            {content.type === 'IMAGE' && '🖼️'}
            {content.type === 'VIDEO' && '🎥'}
            {content.type === 'AUDIO' && '🎵'}
            {content.type === 'DOCUMENT' && '📄'}
          </div>
          <div>
            <p className="font-medium text-gray-900">{content.title}</p>
            <p className="text-sm text-gray-500">{content.category}</p>
          </div>
        </div>
      </td>
      <td className="py-4 px-6">
        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
          content.status === 'ACTIVE' ? 'bg-green-100 text-green-800' :
          content.status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' :
          'bg-red-100 text-red-800'
        }`}>
          {content.status}
        </span>
      </td>
      <td className="py-4 px-6 text-gray-900">${content.revenue.toFixed(2)}</td>
      <td className="py-4 px-6 text-gray-900">{content.views.toLocaleString()}</td>
      <td className="py-4 px-6 text-gray-900">{content.licenses}</td>
      <td className="py-4 px-6 text-gray-500">
        {new Date(content.uploadDate).toLocaleDateString()}
      </td>
    </tr>
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Creator Dashboard</h1>
          <p className="text-gray-600">Manage your content and track your earnings</p>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatCard 
            icon={<span className="text-2xl">💰</span>}
            title="Total Revenue"
            value={`$${stats.totalRevenue.toLocaleString()}`}
            change="+15.3%"
          />
          <StatCard 
            icon={<span className="text-2xl">📤</span>}
            title="Content Uploaded"
            value={stats.contentCount.toString()}
            change="+8.2%"
          />
          <StatCard 
            icon={<span className="text-2xl">👁️</span>}
            title="Total Views"
            value={stats.viewCount.toLocaleString()}
            change="+23.1%"
          />
          <StatCard 
            icon={<span className="text-2xl">🏆</span>}
            title="Licenses Sold"
            value={stats.licensesSold.toLocaleString()}
            change="+11.4%"
          />
        </div>

        {/* Quick Actions */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <button className="bg-blue-600 text-white p-6 rounded-xl hover:bg-blue-700 transition-colors">
            <span className="text-3xl mb-2 block">📤</span>
            <h3 className="text-lg font-semibold">Upload Content</h3>
            <p className="text-sm opacity-90">Add new content to your library</p>
          </button>
          
          <button className="bg-green-600 text-white p-6 rounded-xl hover:bg-green-700 transition-colors">
            <span className="text-3xl mb-2 block">📈</span>
            <h3 className="text-lg font-semibold">View Analytics</h3>
            <p className="text-sm opacity-90">Analyze your content performance</p>
          </button>
          
          <button className="bg-purple-600 text-white p-6 rounded-xl hover:bg-purple-700 transition-colors">
            <span className="text-3xl mb-2 block">⚙️</span>
            <h3 className="text-lg font-semibold">Manage Licenses</h3>
            <p className="text-sm opacity-90">Configure licensing terms</p>
          </button>
        </div>

        {/* Recent Content */}
        <div className="bg-white rounded-xl shadow-lg overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900">Recent Content</h2>
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
                    Revenue
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Views
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Licenses
                  </th>
                  <th className="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Upload Date
                  </th>
                </tr>
              </thead>
              <tbody>
                {recentContent.map((content, index) => (
                  <ContentRow key={index} content={content} />
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CreatorDashboard;
