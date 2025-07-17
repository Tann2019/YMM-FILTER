import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';

export default function YmmFilter({ auth }) {
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
            const response = await axios.get('/api/ymm/makes');
            setMakes(response.data.data);
        } catch (err) {
            setError('Failed to load makes');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadModels = async (make) => {
        try {
            setLoading(true);
            const response = await axios.get('/api/ymm/models', {
                params: { make }
            });
            setModels(response.data.data);
        } catch (err) {
            setError('Failed to load models');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadYearRanges = async (make, model) => {
        try {
            setLoading(true);
            const response = await axios.get('/api/ymm/year-ranges', {
                params: { make, model }
            });
            setYearRanges(response.data.data);
        } catch (err) {
            setError('Failed to load year ranges');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const loadCompatibleProducts = async (make, model, year) => {
        try {
            setLoading(true);
            const response = await axios.get('/api/ymm/compatible-products', {
                params: { make, model, year }
            });
            setCompatibleProducts(response.data.data);
            setError('');
        } catch (err) {
            setError('Failed to load compatible products');
            console.error(err);
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

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">YMM Filter - Bumper Compatibility</h2>}
        >
            <Head title="YMM Filter" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Filter Controls */}
                            <div className="mb-8">
                                <h3 className="text-lg font-medium mb-4">Select Vehicle</h3>
                                
                                {error && (
                                    <div className="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                        {error}
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
                                    </div>

                                    {/* Year Select */}
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
                                    <p className="mt-2 text-gray-600">Loading...</p>
                                </div>
                            )}

                            {/* Selected Vehicle Info */}
                            {selectedMake && selectedModel && selectedYear && (
                                <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                                    <h4 className="font-medium text-blue-900">
                                        Selected Vehicle: {selectedYear} {selectedMake} {selectedModel}
                                    </h4>
                                    <p className="text-blue-700">
                                        Showing {compatibleProducts.length} compatible bumper(s)
                                    </p>
                                </div>
                            )}

                            {/* Products Grid */}
                            {compatibleProducts.length > 0 && (
                                <div>
                                    <h3 className="text-lg font-medium mb-4">Compatible Bumpers</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {compatibleProducts.map((product) => (
                                            <div key={product.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                                                {product.images && product.images.length > 0 && (
                                                    <img
                                                        src={product.images[0].url_standard}
                                                        alt={product.name}
                                                        className="w-full h-48 object-cover rounded mb-4"
                                                    />
                                                )}
                                                <h4 className="font-medium text-gray-900 mb-2">{product.name}</h4>
                                                <p className="text-sm text-gray-600 mb-2">SKU: {product.sku}</p>
                                                <p className="text-lg font-bold text-green-600">
                                                    ${parseFloat(product.price).toFixed(2)}
                                                </p>
                                                {product.description && (
                                                    <p className="text-sm text-gray-500 mt-2 line-clamp-3">
                                                        {product.description.replace(/<[^>]*>/g, '').substring(0, 100)}...
                                                    </p>
                                                )}
                                                <div className="mt-4">
                                                    <a
                                                        href={`https://store-${window.storeHash || 'demo'}.mybigcommerce.com/products/${product.id}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                                                    >
                                                        View Product
                                                    </a>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* No Products Message */}
                            {selectedMake && selectedModel && selectedYear && compatibleProducts.length === 0 && !loading && (
                                <div className="text-center py-8">
                                    <div className="text-gray-500">
                                        <p className="text-lg mb-2">No compatible bumpers found</p>
                                        <p>for {selectedYear} {selectedMake} {selectedModel}</p>
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
