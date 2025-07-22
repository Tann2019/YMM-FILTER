import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import BigCommerceAppLayout from '@/Layouts/BigCommerceAppLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';

export default function WidgetManagement({ auth, widgets: initialWidgets, storeHash, currentUrl, isAppContext }) {
    const [widgets, setWidgets] = useState(initialWidgets || { scripts: [], templates: [], ymmWidgets: [] });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [ngrokUrl, setNgrokUrl] = useState(currentUrl || '');
    const [widgetType, setWidgetType] = useState('template');
    const [showPreview, setShowPreview] = useState(false);
    const [previewHtml, setPreviewHtml] = useState('');

    // New YMM Widget Creation State
    const [showYmmForm, setShowYmmForm] = useState(false);
    const [ymmWidget, setYmmWidget] = useState({
        widget_type: 'template',
        title: 'Find Compatible Products',
        button_text: 'Search Compatible Products',
        background_color: '#f9f9f9',
        text_color: '#333333',
        button_color: '#007cba',
        widget_width: 400
    });

    // Determine API base path based on context
    const getApiPath = (endpoint) => {
        if (isAppContext && storeHash) {
            return `/app/${storeHash}/api/widgets${endpoint}`;
        }
        return `/api/widgets${endpoint}`;
    };

    useEffect(() => {
        loadWidgets();
    }, []);

    const loadWidgets = async () => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get(getApiPath(''));
            setWidgets(response.data.data);
        } catch (err) {
            setError('Failed to load widgets: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const installWidget = async () => {
        if (!ngrokUrl) {
            setError('Please enter a valid URL');
            return;
        }

        try {
            setLoading(true);
            setError('');
            setSuccess('');

            const response = await axios.post(getApiPath('/install'), {
                url: ngrokUrl,
                widget_type: widgetType
            });

            setSuccess(response.data.message);
            await loadWidgets();
        } catch (err) {
            setError('Failed to install widget: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const removeWidget = async (widgetId, widgetType) => {
        if (!confirm('Are you sure you want to remove this widget?')) {
            return;
        }

        try {
            setLoading(true);
            setError('');
            setSuccess('');

            await axios.post(getApiPath('/remove'), {
                widget_id: widgetId,
                widget_type: widgetType
            });

            setSuccess('Widget removed successfully!');
            await loadWidgets();
        } catch (err) {
            setError('Failed to remove widget: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const removeAllWidgets = async () => {
        if (!confirm('Are you sure you want to remove ALL YMM widgets? This cannot be undone.')) {
            return;
        }

        try {
            setLoading(true);
            setError('');
            setSuccess('');

            const response = await axios.post(getApiPath('/remove-all'));
            setSuccess(response.data.message);
            await loadWidgets();
        } catch (err) {
            setError('Failed to remove widgets: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const createYmmWidget = async () => {
        try {
            setLoading(true);
            setError('');
            setSuccess('');

            const response = await axios.post(getApiPath('/create-ymm'), ymmWidget);

            setSuccess(response.data.message);
            
            // Show instructions if available
            if (response.data.instructions) {
                const instructionText = response.data.instructions.join('\n');
                setSuccess(response.data.message + '\n\nInstructions:\n' + instructionText);
            }
            
            await loadWidgets();
            setShowYmmForm(false);
        } catch (err) {
            setError('Failed to create YMM widget: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const getPreview = async () => {
        if (!ngrokUrl) {
            setError('Please enter a URL first');
            return;
        }

        try {
            setLoading(true);
            setError('');

            const response = await axios.post(getApiPath('/preview'), {
                url: ngrokUrl
            });

            setPreviewHtml(response.data.html);
            setShowPreview(true);
        } catch (err) {
            setError('Failed to generate preview: ' + (err.response?.data?.message || err.message));
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Unknown';
        return new Date(dateString).toLocaleString();
    };

    // Choose the appropriate layout based on context
    const Layout = isAppContext ? BigCommerceAppLayout : AuthenticatedLayout;
    const layoutProps = isAppContext 
        ? { storeHash } 
        : { auth };

    return (
        <Layout
            {...layoutProps}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Widget Management</h2>}
        >
            <Head title="Widget Management" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Header */}
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold text-gray-900 mb-2">
                                    BigCommerce Widget Management
                                </h3>
                                <p className="text-gray-600">
                                    Manage YMM widgets for store: <code className="bg-gray-100 px-2 py-1 rounded">{storeHash}</code>
                                </p>
                            </div>

                            {/* Messages */}
                            {error && (
                                <div className="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                    {error}
                                </div>
                            )}

                            {success && (
                                <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                    {success}
                                </div>
                            )}

                            {/* Install New Widget */}
                            <div className="mb-8 p-6 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 className="text-lg font-semibold text-blue-900 mb-4">
                                    🚀 Install New Widget
                                </h4>
                                
                                <div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
                                    <div className="lg:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            API URL (ngrok or production URL)
                                        </label>
                                        <input
                                            type="url"
                                            value={ngrokUrl}
                                            onChange={(e) => setNgrokUrl(e.target.value)}
                                            placeholder="https://abc123.ngrok-free.app"
                                            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <p className="text-xs text-gray-500 mt-1">
                                            Enter your ngrok URL or production domain
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Widget Type
                                        </label>
                                        <select
                                            value={widgetType}
                                            onChange={(e) => setWidgetType(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="template">Page Builder Widget</option>
                                            <option value="script">Global Script</option>
                                        </select>
                                        <p className="text-xs text-gray-500 mt-1">
                                            {widgetType === 'template' ? 'Drag & drop in Page Builder' : 'Loads automatically on all pages'}
                                        </p>
                                    </div>

                                    <div className="flex flex-col justify-end space-y-2">
                                        <button
                                            onClick={getPreview}
                                            disabled={loading || !ngrokUrl}
                                            className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
                                        >
                                            Preview Widget
                                        </button>
                                        <button
                                            onClick={installWidget}
                                            disabled={loading || !ngrokUrl}
                                            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
                                        >
                                            {loading ? 'Installing...' : `Install ${widgetType === 'template' ? 'Page Builder Widget' : 'Global Script'}`}
                                        </button>
                                    </div>
                                </div>
                                
                                {/* Widget Type Info */}
                                <div className="mt-4 p-4 bg-blue-100 border border-blue-200 rounded">
                                    <h5 className="font-semibold text-blue-900 mb-2">Widget Type Information:</h5>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <strong className="text-blue-800">Page Builder Widget:</strong>
                                            <ul className="mt-1 text-blue-700 list-disc list-inside">
                                                <li>Appears in BigCommerce Page Builder</li>
                                                <li>Draggable component with customization options</li>
                                                <li>Can be placed in specific regions</li>
                                                <li>Configurable colors, text, and width</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <strong className="text-blue-800">Global Script:</strong>
                                            <ul className="mt-1 text-blue-700 list-disc list-inside">
                                                <li>Loads automatically on all storefront pages</li>
                                                <li>Creates widget container dynamically</li>
                                                <li>No Page Builder interface needed</li>
                                                <li>Fixed styling and behavior</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Create YMM Widget */}
                            <div className="mb-8 p-6 bg-green-50 border border-green-200 rounded-lg">
                                <div className="flex justify-between items-center mb-4">
                                    <h4 className="text-lg font-semibold text-green-900">
                                        🎯 Create YMM Widget (Recommended)
                                    </h4>
                                    <button
                                        onClick={() => setShowYmmForm(!showYmmForm)}
                                        className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                    >
                                        {showYmmForm ? 'Hide Form' : 'Create YMM Widget'}
                                    </button>
                                </div>
                                
                                <p className="text-green-700 mb-4">
                                    Use this form to create a properly configured YMM filter widget with customization options. 
                                    This is the recommended method for creating YMM widgets.
                                </p>

                                {showYmmForm && (
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Widget Type
                                                </label>
                                                <select
                                                    value={ymmWidget.widget_type}
                                                    onChange={(e) => setYmmWidget({...ymmWidget, widget_type: e.target.value})}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                >
                                                    <option value="template">Page Builder Widget</option>
                                                    <option value="script">Global Script</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Widget Width (px)
                                                </label>
                                                <input
                                                    type="range"
                                                    min="200"
                                                    max="800"
                                                    step="10"
                                                    value={ymmWidget.widget_width}
                                                    onChange={(e) => setYmmWidget({...ymmWidget, widget_width: parseInt(e.target.value)})}
                                                    className="w-full"
                                                />
                                                <div className="text-sm text-gray-600 text-center mt-1">
                                                    {ymmWidget.widget_width}px
                                                </div>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Widget Title
                                                </label>
                                                <input
                                                    type="text"
                                                    value={ymmWidget.title}
                                                    onChange={(e) => setYmmWidget({...ymmWidget, title: e.target.value})}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="Find Compatible Products"
                                                />
                                            </div>
                                            
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Button Text
                                                </label>
                                                <input
                                                    type="text"
                                                    value={ymmWidget.button_text}
                                                    onChange={(e) => setYmmWidget({...ymmWidget, button_text: e.target.value})}
                                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="Search Compatible Products"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Background Color
                                                </label>
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="color"
                                                        value={ymmWidget.background_color}
                                                        onChange={(e) => setYmmWidget({...ymmWidget, background_color: e.target.value})}
                                                        className="w-12 h-10 border border-gray-300 rounded"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={ymmWidget.background_color}
                                                        onChange={(e) => setYmmWidget({...ymmWidget, background_color: e.target.value})}
                                                        className="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Text Color
                                                </label>
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="color"
                                                        value={ymmWidget.text_color}
                                                        onChange={(e) => setYmmWidget({...ymmWidget, text_color: e.target.value})}
                                                        className="w-12 h-10 border border-gray-300 rounded"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={ymmWidget.text_color}
                                                        onChange={(e) => setYmmWidget({...ymmWidget, text_color: e.target.value})}
                                                        className="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Button Color
                                                </label>
                                                <div className="flex items-center space-x-2">
                                                    <input
                                                        type="color"
                                                        value={ymmWidget.button_color}
                                                        onChange={(e) => setYmmWidget({...ymmWidget, button_color: e.target.value})}
                                                        className="w-12 h-10 border border-gray-300 rounded"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={ymmWidget.button_color}
                                                        onChange={(e) => setYmmWidget({...ymmWidget, button_color: e.target.value})}
                                                        className="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        {/* Preview */}
                                        <div className="p-4 bg-white border border-gray-200 rounded-lg">
                                            <h5 className="font-semibold text-gray-900 mb-3">Live Preview:</h5>
                                            <div 
                                                style={{
                                                    maxWidth: `${ymmWidget.widget_width}px`,
                                                    margin: '0 auto',
                                                    padding: '20px',
                                                    border: '1px solid #ddd',
                                                    borderRadius: '8px',
                                                    backgroundColor: ymmWidget.background_color
                                                }}
                                            >
                                                <h3 style={{marginTop: 0, color: ymmWidget.text_color}}>{ymmWidget.title}</h3>
                                                <div style={{marginBottom: '10px'}}>
                                                    <select style={{width: '100%', marginBottom: '8px', padding: '8px'}}>
                                                        <option>Select Year</option>
                                                        <option>2023</option>
                                                        <option>2022</option>
                                                    </select>
                                                </div>
                                                <div style={{marginBottom: '10px'}}>
                                                    <select style={{width: '100%', marginBottom: '8px', padding: '8px'}} disabled>
                                                        <option>Select Make</option>
                                                    </select>
                                                </div>
                                                <div style={{marginBottom: '10px'}}>
                                                    <select style={{width: '100%', marginBottom: '8px', padding: '8px'}} disabled>
                                                        <option>Select Model</option>
                                                    </select>
                                                </div>
                                                <button 
                                                    style={{
                                                        width: '100%',
                                                        padding: '10px',
                                                        backgroundColor: ymmWidget.button_color,
                                                        color: 'white',
                                                        border: 'none',
                                                        borderRadius: '4px',
                                                        cursor: 'not-allowed',
                                                        opacity: 0.6
                                                    }}
                                                    disabled
                                                >
                                                    {ymmWidget.button_text}
                                                </button>
                                            </div>
                                        </div>

                                        <div className="flex justify-end">
                                            <button
                                                onClick={createYmmWidget}
                                                disabled={loading}
                                                className="bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
                                            >
                                                {loading ? 'Creating Widget...' : `Create ${ymmWidget.widget_type === 'template' ? 'Page Builder Widget' : 'Global Script'}`}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Widget Preview */}
                            {showPreview && previewHtml && (
                                <div className="mb-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
                                    <div className="flex justify-between items-center mb-4">
                                        <h4 className="text-lg font-semibold text-gray-900">
                                            Widget Preview
                                        </h4>
                                        <button
                                            onClick={() => setShowPreview(false)}
                                            className="text-gray-500 hover:text-gray-700"
                                        >
                                            ✕ Close
                                        </button>
                                    </div>
                                    <div 
                                        dangerouslySetInnerHTML={{ __html: previewHtml }}
                                        className="widget-preview"
                                    />
                                </div>
                            )}

                            {/* Current YMM Widgets */}
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-4">
                                    <h4 className="text-lg font-semibold text-gray-900">
                                        🎯 Current YMM Widgets ({widgets.ymmWidgets?.length || 0})
                                    </h4>
                                    {widgets.ymmWidgets?.length > 0 && (
                                        <button
                                            onClick={removeAllWidgets}
                                            disabled={loading}
                                            className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
                                        >
                                            Remove All YMM Widgets
                                        </button>
                                    )}
                                </div>

                                {widgets.ymmWidgets?.length > 0 ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {widgets.ymmWidgets.map((widget) => (
                                            <div key={widget.id} className="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                                                <div className="flex justify-between items-start mb-2">
                                                    <h5 className="font-semibold text-gray-900 truncate">
                                                        {widget.name}
                                                    </h5>
                                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                                        widget.type === 'script' 
                                                            ? 'bg-blue-100 text-blue-800' 
                                                            : 'bg-purple-100 text-purple-800'
                                                    }`}>
                                                        {widget.type}
                                                    </span>
                                                </div>
                                                
                                                {widget.description && (
                                                    <p className="text-sm text-gray-600 mb-2">
                                                        {widget.description}
                                                    </p>
                                                )}
                                                
                                                <div className="text-xs text-gray-500 mb-3">
                                                    Created: {formatDate(widget.created_at)}
                                                </div>
                                                
                                                <button
                                                    onClick={() => removeWidget(widget.id, widget.type)}
                                                    disabled={loading}
                                                    className="w-full bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 rounded text-sm focus:outline-none focus:shadow-outline disabled:opacity-50"
                                                >
                                                    Remove Widget
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8 bg-gray-50 rounded-lg">
                                        <p className="text-gray-500 mb-4">No YMM widgets found</p>
                                        <p className="text-sm text-gray-400">Install a widget above to get started</p>
                                    </div>
                                )}
                            </div>

                            {/* All Scripts */}
                            <div className="mb-8">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">
                                    📜 All Scripts ({widgets.scripts?.length || 0})
                                </h4>
                                
                                {widgets.scripts?.length > 0 ? (
                                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                                        <ul className="divide-y divide-gray-200">
                                            {widgets.scripts.map((script) => (
                                                <li key={script.id} className="px-6 py-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center">
                                                                <h5 className="text-sm font-medium text-gray-900">
                                                                    {script.name}
                                                                </h5>
                                                                {script.is_ymm && (
                                                                    <span className="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                                                        YMM
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <p className="text-sm text-gray-600">{script.description}</p>
                                                            <p className="text-xs text-gray-500">
                                                                Location: {script.location} | Created: {formatDate(script.created_at)}
                                                            </p>
                                                        </div>
                                                        
                                                        {script.is_ymm && (
                                                            <button
                                                                onClick={() => removeWidget(script.id, 'script')}
                                                                disabled={loading}
                                                                className="ml-4 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm focus:outline-none focus:shadow-outline disabled:opacity-50"
                                                            >
                                                                Remove
                                                            </button>
                                                        )}
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : (
                                    <div className="text-center py-8 bg-gray-50 rounded-lg">
                                        <p className="text-gray-500">No scripts found</p>
                                    </div>
                                )}
                            </div>

                            {/* All Widget Templates */}
                            <div>
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">
                                    🧩 All Widget Templates ({widgets.templates?.length || 0})
                                </h4>
                                
                                {widgets.templates?.length > 0 ? (
                                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                                        <ul className="divide-y divide-gray-200">
                                            {widgets.templates.map((template) => (
                                                <li key={template.id} className="px-6 py-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center">
                                                                <h5 className="text-sm font-medium text-gray-900">
                                                                    {template.name}
                                                                </h5>
                                                                {template.is_ymm && (
                                                                    <span className="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                                                        YMM
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <p className="text-sm text-gray-600">Kind: {template.kind}</p>
                                                            <p className="text-xs text-gray-500">
                                                                Created: {formatDate(template.created_at)}
                                                            </p>
                                                        </div>
                                                        
                                                        {template.is_ymm && (
                                                            <button
                                                                onClick={() => removeWidget(template.id, 'template')}
                                                                disabled={loading}
                                                                className="ml-4 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm focus:outline-none focus:shadow-outline disabled:opacity-50"
                                                            >
                                                                Remove
                                                            </button>
                                                        )}
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : (
                                    <div className="text-center py-8 bg-gray-50 rounded-lg">
                                        <p className="text-gray-500">No widget templates found</p>
                                    </div>
                                )}
                            </div>

                            {/* Refresh Button */}
                            <div className="mt-8 text-center">
                                <button
                                    onClick={loadWidgets}
                                    disabled={loading}
                                    className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
                                >
                                    {loading ? 'Loading...' : 'Refresh Widgets'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
