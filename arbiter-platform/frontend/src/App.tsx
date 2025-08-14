import React from 'react'
import { BrowserRouter as Router, Routes, Route, Link } from 'react-router-dom'

const Dashboard = () => (
  <div className="p-8">
    <h1 className="text-3xl font-bold mb-6 text-blue-600">Arbiter Platform Dashboard</h1>
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">Total Revenue</h3>
        <p className="text-3xl font-bold text-green-600">$12,543</p>
        <p className="text-sm text-gray-500">+15% from last month</p>
      </div>
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">Active Licenses</h3>
        <p className="text-3xl font-bold text-blue-600">1,247</p>
        <p className="text-sm text-gray-500">+8% from last month</p>
      </div>
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">API Calls</h3>
        <p className="text-3xl font-bold text-purple-600">89,432</p>
        <p className="text-sm text-gray-500">+23% from last month</p>
      </div>
    </div>
  </div>
)

const Publishers = () => (
  <div className="p-8">
    <h1 className="text-3xl font-bold mb-6">Publisher Portal</h1>
    <div className="bg-white p-6 rounded-lg shadow">
      <h2 className="text-xl font-semibold mb-4">Upload Content</h2>
      <p className="text-gray-600 mb-4">Upload your content to start monetizing AI usage</p>
      <button className="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
        Upload Content
      </button>
    </div>
  </div>
)

const AICompanies = () => (
  <div className="p-8">
    <h1 className="text-3xl font-bold mb-6">AI Company Portal</h1>
    <div className="bg-white p-6 rounded-lg shadow">
      <h2 className="text-xl font-semibold mb-4">Browse Content</h2>
      <p className="text-gray-600 mb-4">Find and license content for your AI training</p>
      <button className="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
        Browse Content
      </button>
    </div>
  </div>
)

const Navigation = () => (
  <nav className="bg-blue-600 text-white p-4">
    <div className="container mx-auto flex justify-between items-center">
      <h1 className="text-xl font-bold">Arbiter Platform</h1>
      <div className="space-x-4">
        <Link to="/" className="hover:underline">Dashboard</Link>
        <Link to="/publishers" className="hover:underline">Publishers</Link>
        <Link to="/ai-companies" className="hover:underline">AI Companies</Link>
      </div>
    </div>
  </nav>
)

function App() {
  return (
    <Router>
      <div className="min-h-screen bg-gray-100">
        <Navigation />
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/publishers" element={<Publishers />} />
          <Route path="/ai-companies" element={<AICompanies />} />
        </Routes>
      </div>
    </Router>
  )
}

export default App