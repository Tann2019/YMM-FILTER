import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Settings({ store }) {
    const [activeTab, setActiveTab] = useState('widget');

    const { data, setData, post, processing, errors } = useForm({
        widget_position: 'above_add_to_cart',
        widget_style: 'dropdown',
        primary_color: '#007bff',
        secondary_color: '#6c757d',
        button_text: 'Check Compatibility',
        required_fields: ['year', 'make', 'model'],
        show_reset_button: true,
        enable_autocomplete: true
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/app/${store.store_hash}/settings`, {
            onSuccess: () => {
                // Handle success
            }
        });
    };

    const positionOptions = [
        { value: 'above_add_to_cart', label: 'Above Add to Cart Button' },
        { value: 'below_add_to_cart', label: 'Below Add to Cart Button' },
        { value: 'product_tabs', label: 'In Product Tabs' },
        { value: 'custom', label: 'Custom Position' }
    ];

    const styleOptions = [
        { value: 'dropdown', label: 'Dropdown Selectors' },
        { value: 'inline', label: 'Inline Form' },
        { value: 'modal', label: 'Modal Popup' }
    ];

    return (
        <>
            <Head title="Widget Settings" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h2 className="text-2xl font-bold text-gray-900">Widget Settings</h2>
                                    <p className="text-gray-600">Configure the YMM filter widget for {store.store_url}</p>
                                </div>
                                <div className="flex space-x-4">
                                    <Link
                                        href={`/app/${store.store_hash}/dashboard`}
                                        className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Back to Dashboard
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        {/* Sidebar Navigation */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow-sm rounded-lg overflow-hidden">
                                <nav className="space-y-1">
                                    <button
                                        onClick={() => setActiveTab('widget')}
                                        className={`w-full text-left px-4 py-3 text-sm font-medium ${activeTab === 'widget'
                                                ? 'bg-blue-50 border-r-2 border-blue-500 text-blue-700'
                                                : 'text-gray-600 hover:bg-gray-50'
                                            }`}
                                    >
                                        Widget Configuration
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('appearance')}
                                        className={`w-full text-left px-4 py-3 text-sm font-medium ${activeTab === 'appearance'
                                                ? 'bg-blue-50 border-r-2 border-blue-500 text-blue-700'
                                                : 'text-gray-600 hover:bg-gray-50'
                                            }`}
                                    >
                                        Appearance
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('behavior')}
                                        className={`w-full text-left px-4 py-3 text-sm font-medium ${activeTab === 'behavior'
                                                ? 'bg-blue-50 border-r-2 border-blue-500 text-blue-700'
                                                : 'text-gray-600 hover:bg-gray-50'
                                            }`}
                                    >
                                        Behavior
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('installation')}
                                        className={`w-full text-left px-4 py-3 text-sm font-medium ${activeTab === 'installation'
                                                ? 'bg-blue-50 border-r-2 border-blue-500 text-blue-700'
                                                : 'text-gray-600 hover:bg-gray-50'
                                            }`}
                                    >
                                        Installation
                                    </button>
                                </nav>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="lg:col-span-3">
                            <div className="bg-white shadow-sm rounded-lg overflow-hidden">
                                <form onSubmit={handleSubmit} className="p-6">
                                    {/* Widget Configuration Tab */}
                                    {activeTab === 'widget' && (
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Widget Position</h3>
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Position on Product Page
                                                        </label>
                                                        <select
                                                            value={data.widget_position}
                                                            onChange={(e) => setData('widget_position', e.target.value)}
                                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                                        >
                                                            {positionOptions.map((option) => (
                                                                <option key={option.value} value={option.value}>
                                                                    {option.label}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Widget Style
                                                        </label>
                                                        <select
                                                            value={data.widget_style}
                                                            onChange={(e) => setData('widget_style', e.target.value)}
                                                            className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                                        >
                                                            {styleOptions.map((option) => (
                                                                <option key={option.value} value={option.value}>
                                                                    {option.label}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Required Fields</h3>
                                                <div className="space-y-2">
                                                    {['year', 'make', 'model', 'submodel', 'engine'].map((field) => (
                                                        <label key={field} className="flex items-center">
                                                            <input
                                                                type="checkbox"
                                                                checked={data.required_fields.includes(field)}
                                                                onChange={(e) => {
                                                                    if (e.target.checked) {
                                                                        setData('required_fields', [...data.required_fields, field]);
                                                                    } else {
                                                                        setData('required_fields', data.required_fields.filter(f => f !== field));
                                                                    }
                                                                }}
                                                                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                            />
                                                            <span className="ml-2 text-sm text-gray-700 capitalize">{field}</span>
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Appearance Tab */}
                                    {activeTab === 'appearance' && (
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Colors</h3>
                                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Primary Color
                                                        </label>
                                                        <div className="flex items-center space-x-2">
                                                            <input
                                                                type="color"
                                                                value={data.primary_color}
                                                                onChange={(e) => setData('primary_color', e.target.value)}
                                                                className="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                                                            />
                                                            <input
                                                                type="text"
                                                                value={data.primary_color}
                                                                onChange={(e) => setData('primary_color', e.target.value)}
                                                                className="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Secondary Color
                                                        </label>
                                                        <div className="flex items-center space-x-2">
                                                            <input
                                                                type="color"
                                                                value={data.secondary_color}
                                                                onChange={(e) => setData('secondary_color', e.target.value)}
                                                                className="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                                                            />
                                                            <input
                                                                type="text"
                                                                value={data.secondary_color}
                                                                onChange={(e) => setData('secondary_color', e.target.value)}
                                                                className="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Button Text
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.button_text}
                                                    onChange={(e) => setData('button_text', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                                />
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                                                <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                                    <div className="bg-white p-4 rounded shadow-sm">
                                                        <h4 className="font-medium mb-3">Vehicle Compatibility</h4>
                                                        <div className="grid grid-cols-3 gap-2 mb-3">
                                                            <select
                                                                disabled
                                                                className="px-3 py-2 border border-gray-300 rounded text-sm"
                                                                style={{ borderColor: data.secondary_color }}
                                                            >
                                                                <option>Year</option>
                                                            </select>
                                                            <select
                                                                disabled
                                                                className="px-3 py-2 border border-gray-300 rounded text-sm"
                                                                style={{ borderColor: data.secondary_color }}
                                                            >
                                                                <option>Make</option>
                                                            </select>
                                                            <select
                                                                disabled
                                                                className="px-3 py-2 border border-gray-300 rounded text-sm"
                                                                style={{ borderColor: data.secondary_color }}
                                                            >
                                                                <option>Model</option>
                                                            </select>
                                                        </div>
                                                        <button
                                                            disabled
                                                            className="px-4 py-2 rounded text-white text-sm font-medium"
                                                            style={{ backgroundColor: data.primary_color }}
                                                        >
                                                            {data.button_text}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Behavior Tab */}
                                    {activeTab === 'behavior' && (
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Widget Behavior</h3>
                                                <div className="space-y-4">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="checkbox"
                                                            checked={data.show_reset_button}
                                                            onChange={(e) => setData('show_reset_button', e.target.checked)}
                                                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                        />
                                                        <span className="ml-2 text-sm text-gray-700">Show Reset Button</span>
                                                    </label>

                                                    <label className="flex items-center">
                                                        <input
                                                            type="checkbox"
                                                            checked={data.enable_autocomplete}
                                                            onChange={(e) => setData('enable_autocomplete', e.target.checked)}
                                                            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                        />
                                                        <span className="ml-2 text-sm text-gray-700">Enable Autocomplete</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Installation Tab */}
                                    {activeTab === 'installation' && (
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Installation Instructions</h3>
                                                <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                                    <h4 className="font-medium mb-2">Automatic Installation (Recommended)</h4>
                                                    <p className="text-sm text-gray-600 mb-4">
                                                        The widget will be automatically injected into your product pages based on your position settings above.
                                                    </p>

                                                    <h4 className="font-medium mb-2">Manual Installation</h4>
                                                    <p className="text-sm text-gray-600 mb-2">
                                                        Add this code to your product template where you want the widget to appear:
                                                    </p>
                                                    <div className="bg-gray-800 text-green-400 p-3 rounded text-sm font-mono overflow-x-auto">
                                                        {`<div id="ymm-filter-widget" data-store="${store.store_hash}"></div>
<script src="https://your-app-url.com/widget.js"></script>`}
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">URL Parameter Format</h3>
                                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                    <p className="text-sm text-blue-800 mb-2">
                                                        When customers use the filter, they'll be redirected to URLs like:
                                                    </p>
                                                    <div className="bg-white border border-blue-200 p-3 rounded text-sm font-mono">
                                                        {`${store.store_url}/search?ymm_year=2020&ymm_make=Ford&ymm_model=Bronco`}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Submit Button */}
                                    <div className="mt-6 pt-6 border-t border-gray-200">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                        >
                                            {processing ? 'Saving...' : 'Save Settings'}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
