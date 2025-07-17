import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';

export default function BigCommerceYmmFilter({ auth }) {
    const [selectedMake, setSelectedMake] = useState('');
    const [selectedModel, setSelectedModel] = useState('');
    const [selectedYear, setSelectedYear] = useState('');
    const [makes, setMakes] = useState([]);
    const [models, setModels] = useState([]);
    const [yearRanges, setYearRanges] = useState([]);
    const [compatibleProducts, setCompatibleProducts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    // Load makes on component mount
    useEffect(() => {
        loadMakes();
    }, []);

    // Load models when make changes
    useEffect(() => {
        if (selectedMake) {
            loadModels(selectedMake);
            setSelectedModel('');
            setSelectedYear('');
            setYearRanges([]);
            setCompatibleProducts([]);
        }
    }, [selectedMake]);

    // Load year ranges when model changes
    useEffect(() => {
        if (selectedMake && selectedModel) {
            loadYearRanges(selectedMake, selectedModel);
            setSelectedYear('');
            setCompatibleProducts([]);
        }
    }, [selectedModel]);

    // Load compatible products when year is selected
    useEffect(() => {
        if (selectedMake && selectedModel && selectedYear) {
            loadCompatibleProducts(selectedMake, selectedModel, selectedYear);
        }
    }, [selectedYear]);

    const loadMakes = async () => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('/api/bc-ymm/makes');
            
            // Handle different response structures safely
            const data = response.data?.data || response.data || [];
            setMakes(Array.isArray(data) ? data : []);
        } catch (err) {
            setError('Failed to load makes from BigCommerce');
            console.error('Error loading makes:', err);
            setMakes([]);
        } finally {
            setLoading(false);
        }
    };

    const loadModels = async (make) => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('/api/bc-ymm/models', {
                params: { make }
            });
            
            // Handle different response structures safely
            const data = response.data?.data || response.data || [];
            setModels(Array.isArray(data) ? data : []);
        } catch (err) {
            setError('Failed to load models from BigCommerce');
            console.error('Error loading models:', err);
            setModels([]);
        } finally {
            setLoading(false);
        }
    };

    const loadYearRanges = async (make, model) => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('/api/bc-ymm/year-ranges', {
                params: { make, model }
            });
            
            // Handle different response structures safely
            const data = response.data?.data || response.data || [];
            setYearRanges(Array.isArray(data) ? data : []);
        } catch (err) {
            setError('Failed to load year ranges from BigCommerce');
            console.error('Error loading year ranges:', err);
            setYearRanges([]);
        } finally {
            setLoading(false);
        }
    };

    const loadCompatibleProducts = async (make, model, year) => {
        try {
            setLoading(true);
            setError('');
            const response = await axios.get('/api/bc-ymm/compatible-products', {
                params: { make, model, year }
            });
            
            // Handle different response structures safely
            const data = response.data?.data || response.data || [];
            setCompatibleProducts(Array.isArray(data) ? data : []);
        } catch (err) {
            setError('Failed to load compatible products from BigCommerce');
            console.error('Error loading compatible products:', err);
            setCompatibleProducts([]);
        } finally {
            setLoading(false);
        }
    };

    const resetFilter = () => {
        setSelectedMake('');
        setSelectedModel('');
        setSelectedYear('');
        setModels([]);
        setYearRanges([]);
        setCompatibleProducts([]);
        setError('');
    };

    const getStoreUrl = () => {
        // You'll need to get the store hash from your BigCommerce session
        const storeHash = window.storeHash || 'demo'; 
        return `https://store-${storeHash}.mybigcommerce.com`;
    };

    return (
        <AuthenticatedLayout
            auth={auth}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">BigCommerce YMM Filter - Bumper Compatibility</h2>}
        >
            <Head title="BigCommerce YMM Filter" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Info Banner */}
                            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                                <h3 className="font-medium text-blue-900 mb-2">🛍️ BigCommerce Integration</h3>
                                <p className="text-blue-700 text-sm">
                                    This filter reads compatibility data directly from your BigCommerce product custom fields.
                                    Make sure your bumper products have the correct YMM custom fields configured.
                                </p>
                            </div>

                            {/* Filter Controls */}
                            <div className="mb-8">
                                <h3 className="text-lg font-medium mb-4">Select Vehicle</h3>
                                
                                {error && (
                                    <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                        {error}
                                        <br />
                                        <small className="text-red-600">
                                            Make sure your BigCommerce products have custom fields: ymm_make, ymm_model, ymm_year_start, ymm_year_end
                                        </small>
                                    </div>
                                )}

                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    {/* Make Select */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Make
                                        </label>
                                        <select
                                            value={selectedMake}
                                            onChange={(e) => setSelectedMake(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            disabled={loading}
                                        >
                                            <option value="">Select Make</option>
                                            {makes.map((make) => (
                                                <option key={make} value={make}>
                                                    {make}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Model Select */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Model
                                        </label>
                                        <select
                                            value={selectedModel}
                                            onChange={(e) => setSelectedModel(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            disabled={loading || !selectedMake}
                                        >
                                            <option value="">Select Model</option>
                                            {models.map((model) => (
                                                <option key={model} value={model}>
                                                    {model}
                                                </option>
                                            ))}
                                        </select>
                                    </div>                            {/* Year Select */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Year
                                </label>
                                <select
                                    value={selectedYear}
                                    onChange={(e) => setSelectedYear(e.target.value)}
                                    className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    disabled={loading || !selectedModel}
                                >
                                    <option value="">Select Year</option>
                                    {yearRanges.map((range, index) => {
                                        if (!range || typeof range.start === 'undefined' || typeof range.end === 'undefined') {
                                            return null;
                                        }
                                        // Generate individual years from year ranges
                                        const years = [];
                                        for (let year = range.start; year <= range.end; year++) {
                                            years.push(year);
                                        }
                                        return years.map((year) => (
                                            <option key={`${index}-${year}`} value={year}>
                                                {year}
                                            </option>
                                        ));
                                    })}
                                </select>
                            </div>

                                    {/* Reset Button */}
                                    <div className="flex items-end">
                                        <button
                                            onClick={resetFilter}
                                            className="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Loading Indicator */}
                            {loading && (
                                <div className="text-center py-4">
                                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                    <p className="mt-2 text-gray-600">Loading from BigCommerce...</p>
                                </div>
                            )}

                            {/* Selected Vehicle Info */}
                            {selectedMake && selectedModel && selectedYear && (
                                <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded">
                                    <h4 className="font-medium text-green-900">
                                        ✅ Selected Vehicle: {selectedYear} {selectedMake} {selectedModel}
                                    </h4>
                                    <p className="text-green-700">
                                        Found {compatibleProducts.length} compatible bumper(s) in your BigCommerce store
                                    </p>
                                </div>
                            )}

                            {/* Products Grid */}
                            {compatibleProducts.length > 0 && (
                                <div>
                                    <h3 className="text-lg font-medium mb-4">Compatible Bumpers from BigCommerce</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {compatibleProducts.map((product) => {
                                            // Safe product rendering with null checks
                                            if (!product || !product.id) {
                                                return null;
                                            }
                                            
                                            return (
                                                <div key={product.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                                                    {product.images && product.images.length > 0 && product.images[0]?.url_standard && (
                                                        <img
                                                            src={product.images[0].url_standard}
                                                            alt={product.name || 'Product'}
                                                            className="w-full h-48 object-cover rounded mb-4"
                                                            onError={(e) => {
                                                                e.target.style.display = 'none';
                                                            }}
                                                        />
                                                    )}
                                                    <h4 className="font-medium text-gray-900 mb-2">{product.name || 'Unnamed Product'}</h4>
                                                    <p className="text-sm text-gray-600 mb-2">
                                                        <span className="font-medium">SKU:</span> {product.sku || 'N/A'}
                                                    </p>
                                                    <p className="text-sm text-gray-600 mb-2">
                                                        <span className="font-medium">Product ID:</span> {product.id}
                                                    </p>
                                                    <p className="text-lg font-bold text-green-600">
                                                        ${product.price ? parseFloat(product.price).toFixed(2) : '0.00'}
                                                    </p>
                                                    {product.description && (
                                                        <p className="text-sm text-gray-500 mt-2 line-clamp-3">
                                                            {product.description.replace(/<[^>]*>/g, '').substring(0, 100)}...
                                                        </p>
                                                    )}
                                                    
                                                    {/* Custom Fields Display */}
                                                    {product.custom_fields && product.custom_fields.length > 0 && (
                                                        <div className="mt-3 p-2 bg-gray-50 rounded text-xs">
                                                            <strong>Compatibility:</strong>
                                                            {product.custom_fields.map((field) => {
                                                                if (field && field.name && field.name.startsWith('ymm_')) {
                                                                    return (
                                                                        <div key={field.name}>
                                                                            {field.name.replace('ymm_', '').replace('_', ' ')}: {field.value || 'N/A'}
                                                                        </div>
                                                                    );
                                                                }
                                                                return null;
                                                            })}
                                                        </div>
                                                    )}
                                                    
                                                    <div className="mt-4 flex space-x-2">
                                                        <a
                                                            href={`${getStoreUrl()}/products/${product.custom_url?.url || product.id}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex-1 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm text-center"
                                                        >
                                                            View in Store
                                                        </a>
                                                        <a
                                                            href={`https://store-${window.storeHash || 'demo'}.mybigcommerce.com/manage/products/${product.id}/edit`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex-1 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm text-center"
                                                        >
                                                            Edit Product
                                                        </a>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* No Products Message */}
                            {selectedMake && selectedModel && selectedYear && compatibleProducts.length === 0 && !loading && (
                                <div className="text-center py-8">
                                    <div className="text-gray-500">
                                        <p className="text-lg mb-2">❌ No compatible bumpers found</p>
                                        <p>for {selectedYear} {selectedMake} {selectedModel}</p>
                                        <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                                            <p className="text-sm text-yellow-800">
                                                <strong>Troubleshooting:</strong>
                                            </p>
                                            <ul className="text-sm text-yellow-700 mt-2 text-left list-disc list-inside">
                                                <li>Check that your BigCommerce products have custom fields: ymm_make, ymm_model, ymm_year_start, ymm_year_end</li>
                                                <li>Verify the custom field values match exactly (case-sensitive)</li>
                                                <li>Ensure year ranges include the selected year ({selectedYear})</li>
                                            </ul>
                                        </div>
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
