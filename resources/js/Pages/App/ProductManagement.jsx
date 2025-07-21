import React, { useState, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function ProductManagement({ store, products, vehicles, filters }) {
    const { flash } = usePage().props;
    const [isLoading, setIsLoading] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [selectedVehicles, setSelectedVehicles] = useState([]);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [activeTab, setActiveTab] = useState('products');
    const [compatibilityMode, setCompatibilityMode] = useState('individual'); // 'individual' or 'bulk'
    const [showCompatibilityModal, setShowCompatibilityModal] = useState(false);
    const topRef = useRef(null);

    // Compatibility rule state for bulk assignment
    const [compatibilityRule, setCompatibilityRule] = useState({
        make: '',
        model: '',
        year_start: '',
        year_end: '',
        selected_products: []
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(`/app/${store.store_hash}/products`, {
            search: searchTerm,
            page: 1
        });
    };

    const handleAddCompatibility = () => {
        if (!selectedProduct || selectedVehicles.length === 0) {
            alert('Please select a product and at least one vehicle');
            return;
        }

        setIsLoading(true);
        router.post(`/app/${store.store_hash}/products/compatibility`, {
            product_id: selectedProduct.id,
            vehicle_ids: selectedVehicles
        }, {
            onSuccess: () => {
                setSelectedProduct(null);
                setSelectedVehicles([]);
                setShowCompatibilityModal(false);
                setIsLoading(false);
                // Scroll to top to show success message
                topRef.current?.scrollIntoView({ behavior: 'smooth' });
            },
            onError: () => setIsLoading(false),
            preserveScroll: true
        });
    };

    const openCompatibilityModal = (product) => {
        setSelectedProduct(product);
        setSelectedVehicles([]);
        setShowCompatibilityModal(true);
    };

    const handleBulkCompatibility = () => {
        if (compatibilityRule.selected_products.length === 0) {
            alert('Please select at least one product');
            return;
        }

        if (!compatibilityRule.make || !compatibilityRule.model) {
            alert('Please specify make and model');
            return;
        }

        const compatibleVehicles = vehicles.filter(vehicle => {
            const makeMatch = vehicle.make.toLowerCase() === compatibilityRule.make.toLowerCase();
            const modelMatch = vehicle.model.toLowerCase() === compatibilityRule.model.toLowerCase();

            let yearMatch = true;
            if (compatibilityRule.year_start) {
                yearMatch = yearMatch && vehicle.year_start >= parseInt(compatibilityRule.year_start);
            }
            if (compatibilityRule.year_end) {
                yearMatch = yearMatch && vehicle.year_end <= parseInt(compatibilityRule.year_end);
            }

            return makeMatch && modelMatch && yearMatch;
        });

        if (compatibleVehicles.length === 0) {
            alert('No vehicles found matching the specified criteria');
            return;
        }

        setIsLoading(true);

        // Create compatibility for all selected products with all matching vehicles
        const promises = compatibilityRule.selected_products.map(productId => {
            return router.post(`/app/${store.store_hash}/products/compatibility`, {
                product_id: productId,
                vehicle_ids: compatibleVehicles.map(v => v.id)
            }, {
                preserveScroll: true,
                preserveState: true
            });
        });

        Promise.all(promises).then(() => {
            setCompatibilityRule({
                make: '',
                model: '',
                year_start: '',
                year_end: '',
                selected_products: []
            });
            setIsLoading(false);
        });
    };

    const handleRemoveCompatibility = (productId, vehicleId) => {
        if (confirm('Are you sure you want to remove this compatibility?')) {
            router.delete(`/app/${store.store_hash}/products/compatibility`, {
                data: {
                    product_id: productId,
                    vehicle_id: vehicleId
                },
                preserveScroll: true
            });
        }
    };

    const handleExport = () => {
        window.location.href = `/app/${store.store_hash}/products/export`;
    };

    const handlePageChange = (page) => {
        router.get(`/app/${store.store_hash}/products`, {
            ...filters,
            page: parseInt(page) // Ensure page is sent as integer
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const handleLimitChange = (limit) => {
        router.get(`/app/${store.store_hash}/products`, {
            ...filters,
            limit: parseInt(limit), // Ensure limit is sent as integer
            page: 1 // Reset to first page when changing limit
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const makes = [...new Set(vehicles.map(v => v.make))].sort();

    return (
        <>
            <Head title="Product Search & YMM Compatibility - YMM Filter" />

            <div ref={topRef} className="min-h-screen bg-gray-50">
                {/* Success Message */}
                {flash?.success && (
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                        <div className="bg-green-50 border border-green-200 rounded-md p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-green-800">
                                        {flash.success}
                                    </p>
                                </div>
                                <div className="ml-auto pl-3">
                                    <div className="-mx-1.5 -my-1.5">
                                        <button
                                            type="button"
                                            onClick={() => router.reload({ only: ['flash'] })}
                                            className="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100"
                                        >
                                            <span className="sr-only">Dismiss</span>
                                            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Header */}
                <div className="bg-white shadow">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-6">
                            <div>
                                <Link
                                    href={`/app/${store.store_hash}/dashboard`}
                                    className="text-blue-600 hover:text-blue-800 mb-2 inline-block"
                                >
                                    ← Back to Dashboard
                                </Link>
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Product Search & Compatibility
                                </h1>
                                <p className="text-gray-600 mt-1">
                                    Search products and manage YMM compatibility for your store
                                </p>
                            </div>
                            <button
                                onClick={handleExport}
                                className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
                            >
                                Export Products
                            </button>
                        </div>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Enhanced Search */}
                    <div className="bg-white rounded-lg shadow mb-6 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Product Search</h2>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <input
                                type="text"
                                placeholder="Search by product name, SKU, or description..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <button
                                type="submit"
                                className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </button>
                            {searchTerm && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearchTerm('');
                                        router.get(`/app/${store.store_hash}/products`);
                                    }}
                                    className="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400"
                                >
                                    Clear
                                </button>
                            )}
                        </form>
                        {searchTerm && (
                            <p className="text-sm text-gray-600 mt-2">
                                Searching for: "<strong>{searchTerm}</strong>"
                            </p>
                        )}
                    </div>

                    {/* Bulk Compatibility Assignment (Collapsed by Default) */}
                    <div className="bg-white rounded-lg shadow mb-6">
                        <div className="p-6">
                            <button
                                onClick={() => setCompatibilityMode(compatibilityMode === 'bulk' ? 'none' : 'bulk')}
                                className="flex items-center justify-between w-full text-left"
                            >
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Bulk Compatibility Assignment</h3>
                                    <p className="text-sm text-gray-600 mt-1">
                                        Assign multiple products to vehicles by make/model/year range
                                    </p>
                                </div>
                                <svg
                                    className={`w-5 h-5 transition-transform ${compatibilityMode === 'bulk' ? 'rotate-180' : ''}`}
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {compatibilityMode === 'bulk' && (
                                <div className="mt-6 space-y-4">
                                    <h3 className="text-lg font-medium text-gray-900">Bulk Assignment by Vehicle Type</h3>
                                    <p className="text-sm text-gray-600">
                                        Select multiple products and assign them to all vehicles matching specific criteria.
                                        Much faster for products that fit the same types of vehicles.
                                    </p>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 className="font-medium text-gray-900 mb-3">1. Select Vehicle Criteria</h4>
                                            <div className="space-y-3">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">Make</label>
                                                    <select
                                                        value={compatibilityRule.make}
                                                        onChange={(e) => setCompatibilityRule({ ...compatibilityRule, make: e.target.value, model: '' })}
                                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                                        required
                                                    >
                                                        <option value="">Select Make</option>
                                                        {makes.map(make => (
                                                            <option key={make} value={make}>{make}</option>
                                                        ))}
                                                    </select>
                                                </div>

                                                {compatibilityRule.make && (
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                                        <select
                                                            value={compatibilityRule.model}
                                                            onChange={(e) => setCompatibilityRule({ ...compatibilityRule, model: e.target.value })}
                                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                                            required
                                                        >
                                                            <option value="">Select Model</option>
                                                            {[...new Set(vehicles
                                                                .filter(v => v.make === compatibilityRule.make)
                                                                .map(v => v.model)
                                                            )].sort().map(model => (
                                                                <option key={model} value={model}>{model}</option>
                                                            ))}
                                                        </select>
                                                    </div>
                                                )}

                                                <div className="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">Year From (optional)</label>
                                                        <input
                                                            type="number"
                                                            value={compatibilityRule.year_start}
                                                            onChange={(e) => setCompatibilityRule({ ...compatibilityRule, year_start: e.target.value })}
                                                            placeholder="e.g. 2005"
                                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-1">Year To (optional)</label>
                                                        <input
                                                            type="number"
                                                            value={compatibilityRule.year_end}
                                                            onChange={(e) => setCompatibilityRule({ ...compatibilityRule, year_end: e.target.value })}
                                                            placeholder="e.g. 2020"
                                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 className="font-medium text-gray-900 mb-3">2. Select Products</h4>
                                            <div className="max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                                {products.data?.map((product) => (
                                                    <label key={product.id} className="flex items-center space-x-2 mb-2">
                                                        <input
                                                            type="checkbox"
                                                            checked={compatibilityRule.selected_products.includes(product.id)}
                                                            onChange={(e) => {
                                                                if (e.target.checked) {
                                                                    setCompatibilityRule({
                                                                        ...compatibilityRule,
                                                                        selected_products: [...compatibilityRule.selected_products, product.id]
                                                                    });
                                                                } else {
                                                                    setCompatibilityRule({
                                                                        ...compatibilityRule,
                                                                        selected_products: compatibilityRule.selected_products.filter(id => id !== product.id)
                                                                    });
                                                                }
                                                            }}
                                                            className="rounded border-gray-300"
                                                        />
                                                        <span className="text-sm">
                                                            {product.name} ({product.ymm_count || 0} existing)
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex gap-3 pt-4">
                                        <button
                                            onClick={handleBulkCompatibility}
                                            disabled={isLoading || !compatibilityRule.make || !compatibilityRule.model || compatibilityRule.selected_products.length === 0}
                                            className="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            {isLoading ? 'Processing...' : `Assign ${compatibilityRule.selected_products.length} Products`}
                                        </button>
                                        <button
                                            onClick={() => setCompatibilityRule({
                                                make: '',
                                                model: '',
                                                year_start: '',
                                                year_end: '',
                                                selected_products: []
                                            })}
                                            className="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Products List */}
                    <div className="bg-white rounded-lg shadow">
                        <div className="p-6 border-b border-gray-200 flex justify-between items-center">
                            <h2 className="text-xl font-semibold">
                                Products ({products.meta?.pagination?.total || products.data?.length || 0})
                            </h2>
                            <div className="flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <label className="text-sm text-gray-600">Show:</label>
                                    <select
                                        value={filters.limit || 50}
                                        onChange={(e) => handleLimitChange(parseInt(e.target.value))}
                                        className="border border-gray-300 rounded px-3 py-1 text-sm"
                                    >
                                        <option value={25}>25</option>
                                        <option value={50}>50</option>
                                        <option value={100}>100</option>
                                        <option value={250}>250</option>
                                    </select>
                                    <span className="text-sm text-gray-600">per page</span>
                                </div>
                                {/* Debug info - remove in production */}
                                <div className="text-xs text-gray-500">
                                    Current page: {products.meta?.pagination?.current_page || 1},
                                    Total: {products.meta?.pagination?.total || 0},
                                    Limit: {filters.limit || 50}
                                </div>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Product
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            SKU
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            YMM Compatibility
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {products.data?.map((product) => (
                                        <tr key={product.id}>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    {product.images?.[0] && (
                                                        <img
                                                            className="h-10 w-10 rounded object-cover mr-3"
                                                            src={product.images[0].url_thumbnail}
                                                            alt={product.name}
                                                        />
                                                    )}
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {product.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            ID: {product.id}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                {product.sku || 'N/A'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">
                                                ${product.price}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {product.ymm_count} vehicles
                                                    </span>
                                                    {product.ymm_compatibility?.length > 0 && (
                                                        <div className="mt-2 space-y-1">
                                                            {product.ymm_compatibility.slice(0, 3).map((comp, index) => (
                                                                <div key={index} className="flex items-center justify-between text-xs text-gray-600 bg-gray-50 px-2 py-1 rounded">
                                                                    <span>
                                                                        {comp.year_start}-{comp.year_end} {comp.make} {comp.model}
                                                                    </span>
                                                                    <button
                                                                        onClick={() => handleRemoveCompatibility(product.id, vehicles.find(v => v.make === comp.make && v.model === comp.model && v.year_start === comp.year_start)?.id)}
                                                                        className="text-red-600 hover:text-red-800 ml-2"
                                                                        title="Remove compatibility"
                                                                    >
                                                                        ×
                                                                    </button>
                                                                </div>
                                                            ))}
                                                            {product.ymm_compatibility.length > 3 && (
                                                                <div className="text-xs text-gray-500">
                                                                    +{product.ymm_compatibility.length - 3} more...
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <button
                                                    onClick={() => openCompatibilityModal(product)}
                                                    className="bg-blue-600 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-700 flex items-center gap-1"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Add Compatibility
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Enhanced Pagination */}
                        {products.meta?.pagination && (
                            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                                <div className="text-sm text-gray-700">
                                    Showing {((products.meta.pagination.current_page - 1) * products.meta.pagination.per_page) + 1} to {Math.min(products.meta.pagination.current_page * products.meta.pagination.per_page, products.meta.pagination.total)} of {products.meta.pagination.total} products
                                </div>
                                <div className="flex items-center gap-2">
                                    {products.meta.pagination.current_page > 1 && (
                                        <>
                                            <button
                                                onClick={() => handlePageChange(1)}
                                                className="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
                                            >
                                                First
                                            </button>
                                            <button
                                                onClick={() => handlePageChange(products.meta.pagination.current_page - 1)}
                                                className="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
                                            >
                                                Previous
                                            </button>
                                        </>
                                    )}

                                    <span className="px-3 py-1 text-sm text-gray-600">
                                        Page {products.meta.pagination.current_page} of {products.meta.pagination.total_pages}
                                    </span>

                                    {products.meta.pagination.current_page < products.meta.pagination.total_pages && (
                                        <>
                                            <button
                                                onClick={() => handlePageChange(products.meta.pagination.current_page + 1)}
                                                className="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
                                            >
                                                Next
                                            </button>
                                            <button
                                                onClick={() => handlePageChange(products.meta.pagination.total_pages)}
                                                className="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"
                                            >
                                                Last
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Debug Pagination Info */}
                        <div className="px-6 py-2 bg-gray-50 border-t text-xs text-gray-500">
                            Debug: {JSON.stringify({
                                hasData: !!products.data,
                                dataLength: products.data?.length || 0,
                                hasMeta: !!products.meta,
                                hasPagination: !!products.meta?.pagination,
                                filters: filters,
                                pagination: products.meta?.pagination
                            }, null, 2)}
                        </div>
                    </div>
                </div>
            </div>

            {/* Compatibility Modal */}
            {showCompatibilityModal && selectedProduct && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div className="mt-3">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Add YMM Compatibility
                                </h3>
                                <button
                                    onClick={() => setShowCompatibilityModal(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div className="mb-4 p-3 bg-gray-50 rounded-lg">
                                <div className="flex items-center">
                                    {selectedProduct.images?.[0] && (
                                        <img
                                            className="h-12 w-12 rounded object-cover mr-3"
                                            src={selectedProduct.images[0].url_thumbnail}
                                            alt={selectedProduct.name}
                                        />
                                    )}
                                    <div>
                                        <div className="font-medium text-gray-900">{selectedProduct.name}</div>
                                        <div className="text-sm text-gray-500">SKU: {selectedProduct.sku || 'N/A'}</div>
                                        <div className="text-sm text-gray-500">Current compatibility: {selectedProduct.ymm_count || 0} vehicles</div>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Select Compatible Vehicles:
                                    </label>
                                    <div className="max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                        <div className="space-y-2">
                                            {vehicles.map((vehicle) => (
                                                <label key={vehicle.id} className="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedVehicles.includes(vehicle.id)}
                                                        onChange={(e) => {
                                                            if (e.target.checked) {
                                                                setSelectedVehicles([...selectedVehicles, vehicle.id]);
                                                            } else {
                                                                setSelectedVehicles(selectedVehicles.filter(id => id !== vehicle.id));
                                                            }
                                                        }}
                                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <span className="text-sm text-gray-900">
                                                        <span className="font-medium">{vehicle.make} {vehicle.model}</span>
                                                        <span className="text-gray-500 ml-2">({vehicle.year_start}-{vehicle.year_end})</span>
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                    {selectedVehicles.length > 0 && (
                                        <p className="text-sm text-blue-600 mt-2">
                                            {selectedVehicles.length} vehicle(s) selected
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="flex gap-3 mt-6 pt-4 border-t">
                                <button
                                    onClick={handleAddCompatibility}
                                    disabled={isLoading || selectedVehicles.length === 0}
                                    className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                >
                                    {isLoading ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Adding...
                                        </>
                                    ) : (
                                        `Add Compatibility (${selectedVehicles.length} vehicles)`
                                    )}
                                </button>
                                <button
                                    onClick={() => setShowCompatibilityModal(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
