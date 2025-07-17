import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';

export default function VehicleManagement({ auth }) {
    const [vehicles, setVehicles] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [showAddForm, setShowAddForm] = useState(false);
    const [selectedVehicle, setSelectedVehicle] = useState(null);
    
    // Form state
    const [formData, setFormData] = useState({
        make: '',
        model: '',
        year_start: '',
        year_end: '',
        trim: ''
    });

    // Widget preview state
    const [showWidgetPreview, setShowWidgetPreview] = useState(false);

    useEffect(() => {
        loadVehicles();
    }, []);

    const loadVehicles = async () => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('/api/vehicles');
            
            // Handle different response structures
            const data = response.data?.data || response.data || [];
            setVehicles(Array.isArray(data) ? data : []);
        } catch (err) {
            setError('Failed to load vehicles');
            console.error('Error loading vehicles:', err);
            setVehicles([]);
        } finally {
            setLoading(false);
        }
    };

    const handleAddVehicle = async (e) => {
        e.preventDefault();
        try {
            setLoading(true);
            setError('');
            
            const response = await axios.post('/api/vehicles', formData);
            
            // Reset form and reload data
            setFormData({
                make: '',
                model: '',
                year_start: '',
                year_end: '',
                trim: ''
            });
            setShowAddForm(false);
            await loadVehicles();
            
        } catch (err) {
            setError('Failed to add vehicle: ' + (err.response?.data?.message || err.message));
            console.error('Error adding vehicle:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteVehicle = async (vehicleId) => {
        if (!window.confirm('Are you sure you want to delete this vehicle?')) {
            return;
        }

        try {
            setLoading(true);
            setError('');
            
            await axios.delete(`/api/vehicles/${vehicleId}`);
            await loadVehicles();
            
        } catch (err) {
            setError('Failed to delete vehicle: ' + (err.response?.data?.message || err.message));
            console.error('Error deleting vehicle:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleEditVehicle = (vehicle) => {
        setSelectedVehicle(vehicle);
        setFormData({
            make: vehicle.make || '',
            model: vehicle.model || '',
            year_start: vehicle.year_start || '',
            year_end: vehicle.year_end || '',
            trim: vehicle.trim || ''
        });
        setShowAddForm(true);
    };

    const handleUpdateVehicle = async (e) => {
        e.preventDefault();
        if (!selectedVehicle?.id) return;

        try {
            setLoading(true);
            setError('');
            
            await axios.put(`/api/vehicles/${selectedVehicle.id}`, formData);
            
            // Reset form and reload data
            setFormData({
                make: '',
                model: '',
                year_start: '',
                year_end: '',
                trim: ''
            });
            setShowAddForm(false);
            setSelectedVehicle(null);
            await loadVehicles();
            
        } catch (err) {
            setError('Failed to update vehicle: ' + (err.response?.data?.message || err.message));
            console.error('Error updating vehicle:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const cancelEdit = () => {
        setShowAddForm(false);
        setSelectedVehicle(null);
        setFormData({
            make: '',
            model: '',
            year_start: '',
            year_end: '',
            trim: ''
        });
    };

    return (
        <AuthenticatedLayout
            auth={auth}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Vehicle Management</h2>}
        >
            <Head title="Vehicle Management" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Header with actions */}
                            <div className="flex justify-between items-center mb-6">
                                <div>
                                    <h3 className="text-lg font-medium">Vehicle Database</h3>
                                    <p className="text-gray-600">Manage vehicle compatibility database</p>
                                </div>
                                <div className="flex space-x-3">
                                    <button
                                        onClick={() => setShowWidgetPreview(!showWidgetPreview)}
                                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        {showWidgetPreview ? 'Hide' : 'Show'} Widget Preview
                                    </button>
                                    <button
                                        onClick={() => setShowAddForm(!showAddForm)}
                                        className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        {showAddForm ? 'Cancel' : 'Add Vehicle'}
                                    </button>
                                </div>
                            </div>

                            {/* Widget Preview */}
                            {showWidgetPreview && (
                                <div className="mb-8 p-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg">
                                    <h4 className="text-lg font-medium mb-4">🔧 Widget Preview</h4>
                                    <p className="text-gray-600 mb-4">This is how the widget will appear on your BigCommerce storefront:</p>
                                    
                                    <div className="border-2 border-blue-200 rounded-lg p-4 bg-white">
                                        <iframe 
                                            src="/ymm-widget" 
                                            style={{
                                                width: '100%',
                                                height: '400px',
                                                border: 'none',
                                                borderRadius: '8px'
                                            }}
                                            title="YMM Widget Preview"
                                        />
                                    </div>
                                    
                                    <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
                                        <h5 className="font-medium text-blue-900 mb-2">Integration Instructions:</h5>
                                        <p className="text-blue-700 text-sm mb-2">
                                            Add this code to your BigCommerce theme where you want the filter to appear:
                                        </p>
                                        <pre className="bg-gray-800 text-green-400 p-3 rounded text-sm overflow-x-auto">
{`<iframe src="${window.location.origin}/ymm-widget" 
        style="width: 100%; height: 400px; border: none;">
</iframe>`}
                                        </pre>
                                    </div>
                                </div>
                            )}

                            {/* Error display */}
                            {error && (
                                <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                    {error}
                                </div>
                            )}

                            {/* Add/Edit Form */}
                            {showAddForm && (
                                <div className="mb-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
                                    <h4 className="text-lg font-medium mb-4">
                                        {selectedVehicle ? 'Edit Vehicle' : 'Add New Vehicle'}
                                    </h4>
                                    <form onSubmit={selectedVehicle ? handleUpdateVehicle : handleAddVehicle}>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Make *
                                                </label>
                                                <input
                                                    type="text"
                                                    name="make"
                                                    value={formData.make}
                                                    onChange={handleInputChange}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="e.g., Ford"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Model *
                                                </label>
                                                <input
                                                    type="text"
                                                    name="model"
                                                    value={formData.model}
                                                    onChange={handleInputChange}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="e.g., F-150"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Start Year *
                                                </label>
                                                <input
                                                    type="number"
                                                    name="year_start"
                                                    value={formData.year_start}
                                                    onChange={handleInputChange}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="2015"
                                                    min="1900"
                                                    max="2030"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    End Year *
                                                </label>
                                                <input
                                                    type="number"
                                                    name="year_end"
                                                    value={formData.year_end}
                                                    onChange={handleInputChange}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="2020"
                                                    min="1900"
                                                    max="2030"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Trim (Optional)
                                                </label>
                                                <input
                                                    type="text"
                                                    name="trim"
                                                    value={formData.trim}
                                                    onChange={handleInputChange}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="e.g., XLT"
                                                />
                                            </div>
                                        </div>
                                        <div className="mt-4 flex space-x-3">
                                            <button
                                                type="submit"
                                                disabled={loading}
                                                className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
                                            >
                                                {loading ? 'Saving...' : (selectedVehicle ? 'Update Vehicle' : 'Add Vehicle')}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={cancelEdit}
                                                className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            )}

                            {/* Loading Indicator */}
                            {loading && !showAddForm && (
                                <div className="text-center py-4">
                                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                    <p className="mt-2 text-gray-600">Loading vehicles...</p>
                                </div>
                            )}

                            {/* Vehicles Table */}
                            {!loading && vehicles.length > 0 && (
                                <div className="bg-white shadow overflow-hidden sm:rounded-md">
                                    <div className="px-4 py-5 sm:px-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Vehicles ({vehicles.length})
                                        </h3>
                                        <p className="mt-1 max-w-2xl text-sm text-gray-500">
                                            List of all vehicles in the compatibility database
                                        </p>
                                    </div>
                                    <div className="border-t border-gray-200">
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Make
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Model
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Year Range
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Trim
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Actions
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {vehicles.map((vehicle) => (
                                                        <tr key={vehicle.id || Math.random()}>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                {vehicle.make}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {vehicle.model}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {vehicle.year_start} - {vehicle.year_end}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {vehicle.trim || 'N/A'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                                <button
                                                                    onClick={() => handleEditVehicle(vehicle)}
                                                                    className="text-indigo-600 hover:text-indigo-900"
                                                                >
                                                                    Edit
                                                                </button>
                                                                <button
                                                                    onClick={() => handleDeleteVehicle(vehicle.id)}
                                                                    className="text-red-600 hover:text-red-900"
                                                                >
                                                                    Delete
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* No Vehicles Message */}
                            {!loading && vehicles.length === 0 && (
                                <div className="text-center py-8">
                                    <div className="text-gray-500">
                                        <p className="text-lg mb-2">No vehicles found</p>
                                        <p>Start by adding some vehicles to the database</p>
                                        <button
                                            onClick={() => setShowAddForm(true)}
                                            className="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                        >
                                            Add Your First Vehicle
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
