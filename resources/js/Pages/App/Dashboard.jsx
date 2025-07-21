import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

export default function Dashboard({ store, stats, settings }) {
    const [isLoading, setIsLoading] = useState(false);
    const [isCreatingWidget, setIsCreatingWidget] = useState(false);

    const handleInstallWidget = () => {
        setIsLoading(true);
        router.post(`/app/${store.store_hash}/install-widget`, {}, {
            onFinish: () => setIsLoading(false)
        });
    };

    const handleCreateWidget = () => {
        setIsCreatingWidget(true);
        router.post(`/app/${store.store_hash}/widgets/pagebuilder`, {}, {
            onSuccess: () => {
                // Show success message or redirect
            },
            onError: (errors) => {
                console.error('Widget creation failed:', errors);
            },
            onFinish: () => setIsCreatingWidget(false)
        });
    };

    return (
        <>
            <Head title="YMM Filter Dashboard" />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <div className="bg-white shadow">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-6">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">
                                    YMM Filter Dashboard
                                </h1>
                                <p className="text-gray-600 mt-1">
                                    Store: {store.store_name || store.store_hash}
                                </p>
                            </div>
                            <div className="flex space-x-4">
                                <Link
                                    href={`/app/${store.store_hash}/vehicles`}
                                    className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                                >
                                    Manage Vehicles
                                </Link>
                                <Link
                                    href={`/app/${store.store_hash}/settings`}
                                    className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
                                >
                                    Settings
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="text-3xl font-bold text-blue-600">
                                    {stats.total_vehicles}
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Total Vehicles</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="text-3xl font-bold text-green-600">
                                    {stats.active_vehicles}
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Active Vehicles</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="text-3xl font-bold text-purple-600">
                                    {stats.unique_makes}
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Unique Makes</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="text-3xl font-bold text-orange-600">
                                    {stats.unique_models}
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Unique Models</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Setup Guide */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Setup Guide</h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    <div className="flex items-center">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium ${stats.total_vehicles > 0 ? 'bg-green-500' : 'bg-gray-400'
                                            }`}>
                                            1
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-900">
                                                Add Vehicle Data
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {stats.total_vehicles > 0
                                                    ? `${stats.total_vehicles} vehicles added`
                                                    : 'Import or manually add vehicle compatibility data'
                                                }
                                            </p>
                                        </div>
                                        {stats.total_vehicles === 0 && (
                                            <Link
                                                href={`/app/${store.store_hash}/vehicles`}
                                                className="ml-auto text-blue-600 hover:text-blue-700 text-sm font-medium"
                                            >
                                                Add Now
                                            </Link>
                                        )}
                                    </div>

                                    <div className="flex items-center">
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium ${settings.widget_enabled ? 'bg-green-500' : 'bg-gray-400'
                                            }`}>
                                            2
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-900">
                                                Install Widget
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {settings.widget_enabled
                                                    ? 'Widget is installed and active'
                                                    : 'Install the YMM filter widget on your store'
                                                }
                                            </p>
                                        </div>
                                        {!settings.widget_enabled && (
                                            <button
                                                onClick={handleInstallWidget}
                                                disabled={isLoading}
                                                className="ml-auto bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 disabled:opacity-50"
                                            >
                                                {isLoading ? 'Installing...' : 'Install'}
                                            </button>
                                        )}
                                    </div>

                                    <div className="flex items-center">
                                        <div className="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium bg-gray-400">
                                            3
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-900">
                                                Customize Appearance
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                Configure colors, positioning, and behavior
                                            </p>
                                        </div>
                                        <Link
                                            href={`/app/${store.store_hash}/settings`}
                                            className="ml-auto text-blue-600 hover:text-blue-700 text-sm font-medium"
                                        >
                                            Customize
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Recent Activity */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    <Link
                                        href={`/app/${store.store_hash}/vehicles`}
                                        className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                                    >
                                        <div className="flex items-center">
                                            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </div>
                                            <div className="ml-4">
                                                <h4 className="text-sm font-medium text-gray-900">Manage Vehicles</h4>
                                                <p className="text-sm text-gray-500">Add and manage vehicle compatibility data</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link
                                        href={`/app/${store.store_hash}/products`}
                                        className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                                    >
                                        <div className="flex items-center">
                                            <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <div className="ml-4">
                                                <h4 className="text-sm font-medium text-gray-900">Manage Products</h4>
                                                <p className="text-sm text-gray-500">Add YMM compatibility to your products</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <div className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center">
                                                <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                    <svg className="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z" />
                                                    </svg>
                                                </div>
                                                <div className="ml-4">
                                                    <h4 className="text-sm font-medium text-gray-900">Create Page Builder Widget</h4>
                                                    <p className="text-sm text-gray-500">Add drag-and-drop widget to Page Builder</p>
                                                </div>
                                            </div>
                                            <button
                                                onClick={handleCreateWidget}
                                                disabled={isCreatingWidget}
                                                className="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {isCreatingWidget ? 'Creating...' : 'Create Widget'}
                                            </button>
                                        </div>
                                    </div>

                                    <div className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div className="flex items-center">
                                            <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                                </svg>
                                            </div>
                                            <div className="ml-4">
                                                <p className="text-sm font-medium text-gray-900">Import Data</p>
                                                <p className="text-sm text-gray-500">Bulk import from CSV file</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div className="flex items-center">
                                            <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <div className="ml-4">
                                                <p className="text-sm font-medium text-gray-900">Widget Settings</p>
                                                <p className="text-sm text-gray-500">Configure appearance and behavior</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
