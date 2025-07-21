import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';

export default function VehicleManagement({ store, vehicles, makes, filters = {} }) {
    const [showAddForm, setShowAddForm] = useState(false);
    const [editingVehicle, setEditingVehicle] = useState(null);
    const [showImportForm, setShowImportForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        make: '',
        model: '',
        year_start: '',
        year_end: '',
        trim: '',
        engine: '',
        is_active: true
    });

    const { data: searchData, setData: setSearchData } = useForm({
        search: filters.search || '',
        make: filters.make || ''
    });

    const { data: importData, setData: setImportData, post: postImport, processing: importing } = useForm({
        csv_file: null
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        if (editingVehicle) {
            post(`/app/${store.store_hash}/vehicles/${editingVehicle.id}`, {
                onSuccess: () => {
                    reset();
                    setEditingVehicle(null);
                    setShowAddForm(false);
                }
            });
        } else {
            post(`/app/${store.store_hash}/vehicles`, {
                onSuccess: () => {
                    reset();
                    setShowAddForm(false);
                }
            });
        }
    };

    const handleEdit = (vehicle) => {
        setData({
            make: vehicle.make,
            model: vehicle.model,
            year_start: vehicle.year_start,
            year_end: vehicle.year_end,
            trim: vehicle.trim || '',
            engine: vehicle.engine || '',
            is_active: vehicle.is_active
        });
        setEditingVehicle(vehicle);
        setShowAddForm(true);
    };

    const handleDelete = (vehicleId) => {
        if (confirm('Are you sure you want to delete this vehicle? This will also remove all product associations.')) {
            router.delete(`/app/${store.store_hash}/vehicles/${vehicleId}`);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(`/app/${store.store_hash}/vehicles`, searchData);
    };

    const handleImport = (e) => {
        e.preventDefault();
        postImport(`/app/${store.store_hash}/vehicles/import`, {
            onSuccess: () => {
                setImportData('csv_file', null);
                setShowImportForm(false);
            }
        });
    };

    const handleExport = () => {
        window.location.href = `/app/${store.store_hash}/vehicles/export`;
    };

    return (
        <>
            <Head title="Vehicle Management" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h2 className="text-2xl font-bold text-gray-900">Vehicle Management</h2>
                                    <p className="text-gray-600">Manage Year/Make/Model compatibility data for {store.store_url}</p>
                                </div>
                                <div className="flex space-x-4">
                                    <Link
                                        href={`/app/${store.store_hash}/dashboard`}
                                        className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Back to Dashboard
                                    </Link>
                                    <button
                                        onClick={() => setShowImportForm(true)}
                                        className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Import CSV
                                    </button>
                                    <button
                                        onClick={handleExport}
                                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Export CSV
                                    </button>
                                    <button
                                        onClick={() => setShowAddForm(true)}
                                        className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Vehicle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="flex flex-wrap gap-4 items-end">
                                <div className="flex-1 min-w-64">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Search
                                    </label>
                                    <input
                                        type="text"
                                        value={searchData.search}
                                        onChange={(e) => setSearchData('search', e.target.value)}
                                        placeholder="Search by make, model, or year..."
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div className="min-w-48">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Make
                                    </label>
                                    <select
                                        value={searchData.make}
                                        onChange={(e) => setSearchData('make', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="">All Makes</option>
                                        {makes.map((make) => (
                                            <option key={make} value={make}>
                                                {make}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <button
                                        type="submit"
                                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Search
                                    </button>
                                </div>
                                <div>
                                    <Link
                                        href={`/app/${store.store_hash}/vehicles`}
                                        className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Clear
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Add/Edit Vehicle Form */}
                    {showAddForm && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold">
                                        {editingVehicle ? 'Edit Vehicle' : 'Add New Vehicle'}
                                    </h3>
                                    <button
                                        onClick={() => {
                                            setShowAddForm(false);
                                            setEditingVehicle(null);
                                            reset();
                                        }}
                                        className="text-gray-500 hover:text-gray-700"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Make *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.make}
                                            onChange={(e) => setData('make', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.make && <div className="text-red-600 text-sm mt-1">{errors.make}</div>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Model *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.model}
                                            onChange={(e) => setData('model', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.model && <div className="text-red-600 text-sm mt-1">{errors.model}</div>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Year Start *
                                        </label>
                                        <input
                                            type="number"
                                            value={data.year_start}
                                            onChange={(e) => setData('year_start', e.target.value)}
                                            min="1900"
                                            max="2030"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.year_start && <div className="text-red-600 text-sm mt-1">{errors.year_start}</div>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Year End *
                                        </label>
                                        <input
                                            type="number"
                                            value={data.year_end}
                                            onChange={(e) => setData('year_end', e.target.value)}
                                            min="1900"
                                            max="2030"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                        {errors.year_end && <div className="text-red-600 text-sm mt-1">{errors.year_end}</div>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Trim
                                        </label>
                                        <input
                                            type="text"
                                            value={data.trim}
                                            onChange={(e) => setData('trim', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        {errors.trim && <div className="text-red-600 text-sm mt-1">{errors.trim}</div>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Engine
                                        </label>
                                        <input
                                            type="text"
                                            value={data.engine}
                                            onChange={(e) => setData('engine', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        {errors.engine && <div className="text-red-600 text-sm mt-1">{errors.engine}</div>}
                                    </div>

                                    <div className="md:col-span-2 lg:col-span-3 flex items-center justify-between">
                                        <div className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={data.is_active}
                                                onChange={(e) => setData('is_active', e.target.checked)}
                                                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            />
                                            <label className="ml-2 block text-sm text-gray-900">
                                                Active
                                            </label>
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                        >
                                            {processing ? 'Saving...' : (editingVehicle ? 'Update Vehicle' : 'Add Vehicle')}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* Import Form */}
                    {showImportForm && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold">Import Vehicles from CSV</h3>
                                    <button
                                        onClick={() => setShowImportForm(false)}
                                        className="text-gray-500 hover:text-gray-700"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div className="mb-4 text-sm text-gray-600">
                                    <p>CSV should have columns: make, model, year_start, year_end, trim, engine</p>
                                    <p>First row should contain headers.</p>
                                </div>

                                <form onSubmit={handleImport}>
                                    <div className="mb-4">
                                        <input
                                            type="file"
                                            accept=".csv,.txt"
                                            onChange={(e) => setImportData('csv_file', e.target.files[0])}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            required
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={importing}
                                        className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                    >
                                        {importing ? 'Importing...' : 'Import Vehicles'}
                                    </button>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* Vehicles Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold">
                                    Vehicles ({vehicles.total} total)
                                </h3>
                            </div>

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
                                                Years
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Trim
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Engine
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {vehicles.data.map((vehicle) => (
                                            <tr key={vehicle.id}>
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
                                                    {vehicle.trim || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {vehicle.engine || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${vehicle.is_active
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                        }`}>
                                                        {vehicle.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button
                                                        onClick={() => handleEdit(vehicle)}
                                                        className="text-indigo-600 hover:text-indigo-900 mr-3"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(vehicle.id)}
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

                            {/* Pagination */}
                            {vehicles.last_page > 1 && (
                                <div className="mt-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-700">
                                        Showing {vehicles.from} to {vehicles.to} of {vehicles.total} results
                                    </div>
                                    <div className="flex space-x-1">
                                        {vehicles.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-2 text-sm rounded ${link.active
                                                        ? 'bg-blue-500 text-white'
                                                        : link.url
                                                            ? 'bg-white text-gray-500 hover:bg-gray-50 border border-gray-300'
                                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
