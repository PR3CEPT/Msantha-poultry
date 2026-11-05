import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { Sale, ProductCategory, PaymentMethod } from '../../types';
import { formatDate, formatMWK } from '../../utils/i18n';
import { ReceiptModal } from '../common/ReceiptModal';
import {
  ShoppingBag,
  Plus,
  Truck,
  DollarSign,
  Printer,
  CheckCircle,
  Search,
  Users,
  ChevronRight,
  Calculator,
  Eye,
} from 'lucide-react';
import { PoultryPriceCalculatorModal } from '../common/PoultryPriceCalculatorModal';

export const SalesDistribution: React.FC = () => {
  const { db, currentUser, addSale, canRecordSales, isViewerOnly, metrics } = useFarmData();

  const [activeTab, setActiveTab] = useState<'sales' | 'distribution' | 'customers'>('sales');
  const [search, setSearch] = useState('');
  const [showSaleModal, setShowSaleModal] = useState(false);
  const [selectedReceiptSale, setSelectedReceiptSale] = useState<Sale | null>(null);
  const [showPriceCalcModal, setShowPriceCalcModal] = useState(false);

  // Sale form state
  const today = new Date().toISOString().split('T')[0];
  const [saleDate, setSaleDate] = useState(today);
  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('+265 ');
  const [customerAddress, setCustomerAddress] = useState('Liwonde');
  const [productCategory, setProductCategory] = useState<ProductCategory>('fertilized_eggs');
  const [description, setDescription] = useState('Fertilized Hatching Eggs (Trays of 30)');
  const [quantity, setQuantity] = useState(10);
  const [unit, setUnit] = useState<Sale['unit']>('trays');
  const [unitPrice, setUnitPrice] = useState(8500);
  const [discount, setDiscount] = useState(0);
  const [paymentReceived, setPaymentReceived] = useState(85000);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('cash');
  const [deliveryStatus, setDeliveryStatus] = useState<Sale['deliveryStatus']>('collected');
  const [driver, setDriver] = useState('Sikizwayo Chipeta');
  const [notes, setNotes] = useState('');
  const [categoryFilter, setCategoryFilter] = useState<string>('all');

  const total = quantity * unitPrice - discount;

  const handleCreateSale = (e: React.FormEvent) => {
    e.preventDefault();
    if (!customerName) return;

    const invoiceNumber = `INV-2026-${String(db.sales.length + 84).padStart(3, '0')}`;

    addSale({
      invoiceNumber,
      date: saleDate,
      customerName,
      customerPhone,
      customerAddress,
      productCategory,
      description,
      quantity: Number(quantity),
      unit,
      unitPrice: Number(unitPrice),
      discount: Number(discount),
      total,
      paymentReceived: Number(paymentReceived),
      paymentMethod,
      deliveryStatus,
      driverOrDeliverer: driver,
      salesperson: currentUser.name,
      notes,
    });

    setShowSaleModal(false);
  };

  const filteredSales = db.sales.filter((s) => {
    const matchSearch =
      s.customerName.toLowerCase().includes(search.toLowerCase()) ||
      s.invoiceNumber.toLowerCase().includes(search.toLowerCase()) ||
      s.description.toLowerCase().includes(search.toLowerCase());

    const matchCategory =
      categoryFilter === 'all' ||
      s.productCategory === categoryFilter ||
      (categoryFilter === 'fertilized_eggs' && (s.productCategory === 'fertilized_eggs' || s.productCategory === 'eggs')) ||
      (categoryFilter === 'quails' && (s.productCategory === 'quails' || s.description.toLowerCase().includes('quail') || s.description.toLowerCase().includes('qual'))) ||
      (categoryFilter === 'ducklings' && s.productCategory === 'ducklings') ||
      (categoryFilter === 'ducks' && (s.productCategory === 'ducks' || s.description.toLowerCase().includes('duck'))) ||
      (categoryFilter === 'damaged_eggs' && s.productCategory === 'damaged_eggs') ||
      (categoryFilter === 'infertile_eggs' && s.productCategory === 'infertile_eggs') ||
      (categoryFilter === 'poultry' && (['quails', 'ducklings', 'ducks', 'day_old_chicks', 'live_birds', 'turkeys', 'guinea_fowl', 'spent_hens'] as ProductCategory[]).includes(s.productCategory));

    return matchSearch && matchCategory;
  });

  const totalSalesRevenue = db.sales.reduce((sum, s) => sum + s.total, 0);
  const totalSalesCollected = db.sales.reduce((sum, s) => sum + s.paymentReceived, 0);
  const totalReceivables = db.sales.reduce((sum, s) => sum + s.balanceDue, 0);

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">
            Sales, Distribution & Customer Ledger
          </h2>
          <p className="text-xs text-slate-500">
            Managed by Sikizwayo Chipeta • Fertilized eggs, live birds, ducklings, quails, turkeys & receipts.
          </p>
        </div>

        <div className="flex items-center gap-2 shrink-0">
          <button
            onClick={() => setShowPriceCalcModal(true)}
            className="flex items-center justify-center gap-1.5 px-3 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-xs transition"
          >
            <Calculator className="w-3.5 h-3.5" />
            <span>Price Matrix Rate Card</span>
          </button>

          {canRecordSales ? (
            <button
              onClick={() => setShowSaleModal(true)}
              className="flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
            >
              <Plus className="w-4 h-4" />
              <span>Record New Sale</span>
            </button>
          ) : (
            <div className="px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 text-xs font-semibold flex items-center gap-1.5">
              <Eye className="w-3.5 h-3.5" />
              <span>Sales Disabled (Viewing)</span>
            </div>
          )}
        </div>
      </div>

      {/* Cumulative Farm Performance To-Date Banner */}
      <div className="bg-gradient-to-r from-emerald-950 via-slate-900 to-teal-950 text-white p-4 rounded-2xl shadow-sm border border-emerald-900/50">
        <div className="flex items-center justify-between mb-3 border-b border-emerald-800/40 pb-2">
          <div className="flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span className="text-[11px] font-bold tracking-wider uppercase text-emerald-300">
              Msantha Farm Performance Cumulative To-Date
            </span>
          </div>
          <span className="text-[10px] text-slate-400 font-medium">All recorded farm production & sales</span>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <div className="text-[10px] uppercase font-bold text-slate-300">Eggs Collected To Date</div>
            <div className="text-xl sm:text-2xl font-black text-amber-300 mt-0.5">
              {metrics.totalEggsCollectedToDate.toLocaleString()}
            </div>
            <div className="text-[11px] text-slate-300 mt-0.5">
              {Math.floor(metrics.totalEggsCollectedToDate / 30).toLocaleString()} trays (30/tray)
            </div>
          </div>

          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <div className="text-[10px] uppercase font-bold text-slate-300">Eggs Sold To Date</div>
            <div className="text-xl sm:text-2xl font-black text-emerald-400 mt-0.5">
              {metrics.totalEggTraysSoldToDate.toLocaleString()} <span className="text-xs font-semibold">trays</span>
            </div>
            <div className="text-[11px] text-slate-300 mt-0.5">
              {formatMWK(metrics.totalEggSalesRevenueToDate)} ({metrics.totalEggsSoldUnitsToDate.toLocaleString()} eggs)
            </div>
          </div>

          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <div className="text-[10px] uppercase font-bold text-slate-300">Poultry Sales To Date</div>
            <div className="text-xl sm:text-2xl font-black text-teal-300 mt-0.5">
              {metrics.totalPoultryBirdsSoldToDate.toLocaleString()} <span className="text-xs font-semibold">birds</span>
            </div>
            <div className="text-[11px] text-slate-300 mt-0.5">
              {formatMWK(metrics.totalPoultrySalesRevenueToDate)} (Ducks, Quails, DOC, Chickens)
            </div>
          </div>

          <div className="bg-white/5 border border-white/10 rounded-xl p-3">
            <div className="text-[10px] uppercase font-bold text-slate-300">Total Farm Revenue To Date</div>
            <div className="text-xl sm:text-2xl font-black text-white mt-0.5">
              {formatMWK(totalSalesRevenue)}
            </div>
            <div className="text-[11px] text-emerald-300 mt-0.5">
              {formatMWK(totalSalesCollected)} cash received
            </div>
          </div>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-slate-500">Total Sales Invoices</span>
          <div className="text-2xl font-black text-emerald-950 mt-1">
            {formatMWK(totalSalesRevenue)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">{db.sales.length} customer invoices</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-emerald-700">Cash & Mobile Payments Collected</span>
          <div className="text-2xl font-black text-emerald-700 mt-1">
            {formatMWK(totalSalesCollected)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">Airtel Money, Mpamba & Cash</p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <span className="text-[10px] uppercase font-bold text-amber-800">Outstanding Receivables (Credit)</span>
          <div className="text-2xl font-black text-amber-900 mt-1">
            {formatMWK(totalReceivables)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">Balance to collect from customers</p>
        </div>
      </div>

      {/* Tabs & Search */}
      <div className="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div className="flex p-1 bg-slate-100 rounded-lg w-full sm:w-auto text-xs font-semibold overflow-x-auto">
          <button
            onClick={() => setActiveTab('sales')}
            className={`px-4 py-1.5 rounded-md transition whitespace-nowrap ${
              activeTab === 'sales' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Sales Invoices ({db.sales.length})
          </button>
          <button
            onClick={() => setActiveTab('distribution')}
            className={`px-4 py-1.5 rounded-md transition whitespace-nowrap ${
              activeTab === 'distribution' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Delivery Logistics ({db.sales.filter((s) => s.deliveryStatus !== 'collected').length})
          </button>
          <button
            onClick={() => setActiveTab('customers')}
            className={`px-4 py-1.5 rounded-md transition whitespace-nowrap ${
              activeTab === 'customers' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Customer Directory ({db.customers.length})
          </button>
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input
            type="text"
            placeholder="Search sales or customers..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-slate-50 focus:bg-white focus:outline-hidden"
          />
        </div>
      </div>

      {/* Tab 1: Sales Invoices */}
      {activeTab === 'sales' && (
        <div className="space-y-3">
          {/* Category Filter Pills */}
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
            <span className="text-[11px] font-semibold text-slate-500 whitespace-nowrap mr-1">Filter by:</span>
            {[
              { id: 'all', label: 'All Products' },
              { id: 'fertilized_eggs', label: '🥚 Fertilized Eggs' },
              { id: 'damaged_eggs', label: '🍳 Damaged Eggs' },
              { id: 'infertile_eggs', label: '🔍 Infertile Eggs' },
              { id: 'quails', label: '🐦 Quals' },
              { id: 'ducklings', label: '🦆 Ducklings' },
              { id: 'ducks', label: '🦆 Ducks' },
              { id: 'poultry', label: '🐔 All Poultry' },
            ].map((chip) => (
              <button
                key={chip.id}
                onClick={() => setCategoryFilter(chip.id)}
                className={`px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap transition ${
                  categoryFilter === chip.id
                    ? 'bg-emerald-900 text-white shadow-xs'
                    : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
                }`}
              >
                {chip.label}
              </button>
            ))}
          </div>

          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                  <th className="p-3">Invoice / Date</th>
                  <th className="p-3">Customer</th>
                  <th className="p-3">Product Description</th>
                  <th className="p-3 text-right">Quantity</th>
                  <th className="p-3 text-right">Total (MK)</th>
                  <th className="p-3 text-right text-emerald-800">Paid</th>
                  <th className="p-3 text-right text-amber-800">Balance</th>
                  <th className="p-3">Method</th>
                  <th className="p-3">Delivery</th>
                  <th className="p-3 text-center">Receipt</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredSales.map((sale) => (
                  <tr key={sale.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 whitespace-nowrap">
                      <div className="font-mono font-bold text-slate-900">{sale.invoiceNumber}</div>
                      <div className="text-[10px] text-slate-400">{formatDate(sale.date)}</div>
                    </td>
                    <td className="p-3">
                      <div className="font-semibold text-slate-900">{sale.customerName}</div>
                      {sale.customerPhone && <div className="text-[10px] text-slate-500">{sale.customerPhone}</div>}
                    </td>
                    <td className="p-3 font-medium text-slate-800">{sale.description}</td>
                    <td className="p-3 text-right font-bold text-slate-900">
                      {sale.quantity} {sale.unit}
                    </td>
                    <td className="p-3 text-right font-black text-slate-900">{formatMWK(sale.total)}</td>
                    <td className="p-3 text-right font-bold text-emerald-800">{formatMWK(sale.paymentReceived)}</td>
                    <td className="p-3 text-right font-bold text-amber-800">
                      {sale.balanceDue > 0 ? formatMWK(sale.balanceDue) : '-'}
                    </td>
                    <td className="p-3 text-slate-600 uppercase text-[10px] font-semibold">
                      {sale.paymentMethod.replace('_', ' ')}
                    </td>
                    <td className="p-3 capitalize text-slate-700">{sale.deliveryStatus.replace('_', ' ')}</td>
                    <td className="p-3 text-center">
                      <button
                        onClick={() => setSelectedReceiptSale(sale)}
                        className="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        title="Print / WhatsApp Receipt"
                      >
                        <Printer className="w-3.5 h-3.5" />
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

      {/* Tab 2: Delivery & Distribution Logistics */}
      {activeTab === 'distribution' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {db.sales
              .filter((s) => s.deliveryStatus !== 'collected')
              .map((sale) => (
                <div key={sale.id} className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs space-y-3">
                  <div className="flex justify-between items-center text-xs">
                    <span className="font-mono font-bold text-slate-600">{sale.invoiceNumber}</span>
                    <span
                      className={`text-[10px] font-bold px-2 py-0.5 rounded capitalize ${
                        sale.deliveryStatus === 'delivered'
                          ? 'bg-emerald-100 text-emerald-800'
                          : 'bg-amber-100 text-amber-800'
                      }`}
                    >
                      {sale.deliveryStatus.replace('_', ' ')}
                    </span>
                  </div>

                  <div>
                    <h3 className="font-bold text-slate-900 text-sm">{sale.customerName}</h3>
                    <p className="text-xs text-slate-600">{sale.customerAddress || 'Liwonde area'}</p>
                    <p className="text-xs text-slate-500 mt-0.5">{sale.customerPhone}</p>
                  </div>

                  <div className="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-xs">
                    <p className="font-semibold text-slate-800">{sale.description}</p>
                    <p className="text-[11px] text-slate-500 mt-0.5">
                      Quantity: {sale.quantity} {sale.unit}
                    </p>
                    <p className="text-[11px] text-slate-600 mt-1">
                      Deliverer: <strong>{sale.driverOrDeliverer || 'Sikizwayo Chipeta'}</strong>
                    </p>
                  </div>

                  <div className="flex justify-between items-center pt-2 border-t border-slate-100 text-xs">
                    <span className="font-semibold text-slate-700">Total: {formatMWK(sale.total)}</span>
                    <button
                      onClick={() => setSelectedReceiptSale(sale)}
                      className="text-xs font-semibold text-emerald-800 hover:underline flex items-center gap-1"
                    >
                      <span>Receipt</span>
                      <ChevronRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              ))}
          </div>
        </div>
      )}

      {/* Tab 3: Customer Directory */}
      {activeTab === 'customers' && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {db.customers.map((cust) => {
            // Calculate customer stats
            const custSales = db.sales.filter((s) => s.customerId === cust.id || s.customerName === cust.name);
            const custIncubations = db.customerIncubations.filter((b) => b.customerId === cust.id || b.customerName === cust.name);

            const totalSpent =
              custSales.reduce((sum, s) => sum + s.total, 0) +
              custIncubations.reduce((sum, b) => sum + b.totalCharge, 0);

            const balance =
              custSales.reduce((sum, s) => sum + s.balanceDue, 0) +
              custIncubations.reduce((sum, b) => sum + b.balanceDue, 0);

            return (
              <div key={cust.id} className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs space-y-2.5">
                <div className="flex justify-between items-center text-xs">
                  <span className="font-mono text-[11px] text-slate-500 font-bold">{cust.customerCode}</span>
                  <span className="text-[10px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded capitalize">
                    {cust.category.replace('_', ' ')}
                  </span>
                </div>

                <h3 className="font-bold text-slate-900 text-sm">{cust.name}</h3>
                <p className="text-xs text-slate-600">{cust.phone}</p>
                <p className="text-xs text-slate-500">{cust.addressVillage}</p>

                <div className="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                  <div>
                    <span className="text-[10px] text-slate-400 uppercase font-semibold">Total Business</span>
                    <p className="font-bold text-slate-800">{formatMWK(totalSpent)}</p>
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 uppercase font-semibold">Balance Due</span>
                    <p className={`font-bold ${balance > 0 ? 'text-amber-800' : 'text-emerald-700'}`}>
                      {formatMWK(balance)}
                    </p>
                  </div>
                </div>

                {cust.notes && <p className="text-[11px] text-slate-400 italic pt-1">{cust.notes}</p>}
              </div>
            );
          })}
        </div>
      )}

      {/* Record Sale Modal */}
      {showSaleModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
          <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto max-h-[92vh] overflow-y-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Record Farm Sale & Issue Invoice</h3>
            <p className="text-xs text-slate-500 mb-4">
              Record sales of eggs, live birds, chicks, or spent hens.
            </p>

            <form onSubmit={handleCreateSale} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Customer / Buyer Name</label>
                  <input
                    type="text"
                    placeholder="e.g. Shire River Safari Lodge"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Customer Phone</label>
                  <input
                    type="tel"
                    value={customerPhone}
                    onChange={(e) => setCustomerPhone(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Customer Location / Delivery Address</label>
                <input
                  type="text"
                  placeholder="e.g. Liwonde Trading Centre"
                  value={customerAddress}
                  onChange={(e) => setCustomerAddress(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Product Category</label>
                  <select
                    value={productCategory}
                    onChange={(e) => {
                      const cat = e.target.value as ProductCategory;
                      setProductCategory(cat);
                      if (cat === 'fertilized_eggs') {
                        setDescription('Fertilized Hatching Eggs (Trays of 30)');
                        setUnit('trays');
                        setUnitPrice(8500);
                        setPaymentReceived(quantity * 8500 - discount);
                      } else if (cat === 'damaged_eggs') {
                        setDescription('Damaged / Slightly Cracked Eggs (Bakery / Cooking)');
                        setUnit('trays');
                        setUnitPrice(2500);
                        setPaymentReceived(quantity * 2500 - discount);
                      } else if (cat === 'infertile_eggs') {
                        setDescription('Infertile / Candled Clear Eggs (Day 7-10 Clears)');
                        setUnit('trays');
                        setUnitPrice(3000);
                        setPaymentReceived(quantity * 3000 - discount);
                      } else if (cat === 'quails') {
                        setDescription('Quals (Live Quails - Mature / Growers)');
                        setUnit('birds');
                        setUnitPrice(4000);
                        setPaymentReceived(quantity * 4000 - discount);
                      } else if (cat === 'ducklings') {
                        setDescription('Day-Old Ducklings (Brooding Ducklings)');
                        setUnit('birds');
                        setUnitPrice(10000);
                        setPaymentReceived(quantity * 10000 - discount);
                      } else if (cat === 'ducks') {
                        setDescription('Mature Farm Ducks (Table & Breeding)');
                        setUnit('birds');
                        setUnitPrice(25000);
                        setPaymentReceived(quantity * 25000 - discount);
                      } else if (cat === 'day_old_chicks') {
                        setDescription('Day-old vaccinated Mikolongwe / Sasso chicks');
                        setUnit('birds');
                        setUnitPrice(3000);
                        setPaymentReceived(quantity * 3000 - discount);
                      } else if (cat === 'live_birds') {
                        setDescription('Live table birds (Mikolongwe / Sasso)');
                        setUnit('birds');
                        setUnitPrice(8500);
                        setPaymentReceived(quantity * 8500 - discount);
                      } else if (cat === 'turkeys') {
                        setDescription('Turkeys / Poults (Table & Breeding)');
                        setUnit('birds');
                        setUnitPrice(15000);
                        setPaymentReceived(quantity * 15000 - discount);
                      } else if (cat === 'guinea_fowl') {
                        setDescription('Guinea Fowl (Keets / Mature Nkhanga)');
                        setUnit('birds');
                        setUnitPrice(12000);
                        setPaymentReceived(quantity * 12000 - discount);
                      } else if (cat === 'spent_hens') {
                        setDescription('Spent layer hens');
                        setUnit('birds');
                        setUnitPrice(5000);
                        setPaymentReceived(quantity * 5000 - discount);
                      } else if (cat === 'incubation_service') {
                        setDescription('Commercial egg incubation service fee');
                        setUnit('service');
                        setUnitPrice(250);
                        setPaymentReceived(quantity * 250 - discount);
                      } else {
                        setDescription('Organic poultry manure (50kg bags)');
                        setUnit('bags');
                        setUnitPrice(3500);
                        setPaymentReceived(quantity * 3500 - discount);
                      }
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white font-medium"
                  >
                    <option value="fertilized_eggs">🥚 Fertilized Eggs (Hatching)</option>
                    <option value="damaged_eggs">🍳 Damaged Eggs (Bakery / Cooking)</option>
                    <option value="infertile_eggs">🔍 Infertile Eggs (Candled Clears)</option>
                    <option value="quails">🐦 Quals (Live Quails)</option>
                    <option value="ducklings">🦆 Ducklings (Day-Old)</option>
                    <option value="ducks">🦆 Ducks (Mature / Grower)</option>
                    <option value="day_old_chicks">🐣 Day-Old Chicks (DOC)</option>
                    <option value="live_birds">🐔 Live Chickens (Mikolongwe / Sasso)</option>
                    <option value="turkeys">🦃 Turkeys / Poults</option>
                    <option value="guinea_fowl">🪶 Guinea Fowl (Nkhanga)</option>
                    <option value="spent_hens">🐔 Spent Hens</option>
                    <option value="incubation_service">🔥 Incubation Service</option>
                    <option value="manure_other">🌱 Manure / Other</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Item Description</label>
                  <input
                    type="text"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-2">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Quantity</label>
                  <input
                    type="number"
                    min="1"
                    value={quantity}
                    onChange={(e) => {
                      const q = Number(e.target.value);
                      setQuantity(q);
                      setPaymentReceived(q * unitPrice - discount);
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Unit</label>
                  <select
                    value={unit}
                    onChange={(e) => setUnit(e.target.value as any)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="trays">Trays (30 eggs)</option>
                    <option value="individual_eggs">Individual Eggs</option>
                    <option value="birds">Birds</option>
                    <option value="bags">Bags (Manure)</option>
                  </select>
                </div>
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <label className="font-semibold text-slate-700">Unit Price (MK)</label>
                    <button
                      type="button"
                      onClick={() => setShowPriceCalcModal(true)}
                      className="text-[10px] text-emerald-800 hover:underline font-bold flex items-center gap-0.5"
                    >
                      <Calculator className="w-3 h-3 text-amber-500" />
                      <span>Rate Matrix</span>
                    </button>
                  </div>
                  <input
                    type="number"
                    value={unitPrice}
                    onChange={(e) => {
                      const p = Number(e.target.value);
                      setUnitPrice(p);
                      setPaymentReceived(quantity * p - discount);
                    }}
                    className="w-full p-2 border border-slate-300 rounded-lg font-bold"
                    required
                  />
                </div>
              </div>

              {/* Total & Payment */}
              <div className="bg-emerald-50/60 p-3 rounded-xl border border-emerald-200 space-y-2">
                <div className="flex justify-between items-center text-sm font-bold text-emerald-950">
                  <span>Total Sale Value:</span>
                  <span className="text-base">{formatMWK(total)}</span>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block font-semibold text-slate-700 mb-0.5">Payment Received (MK)</label>
                    <input
                      type="number"
                      value={paymentReceived}
                      onChange={(e) => setPaymentReceived(Number(e.target.value))}
                      className="w-full p-1.5 border border-emerald-300 rounded-lg font-bold bg-white"
                      required
                    />
                  </div>
                  <div>
                    <label className="block font-semibold text-slate-700 mb-0.5">Payment Method</label>
                    <select
                      value={paymentMethod}
                      onChange={(e) => setPaymentMethod(e.target.value as PaymentMethod)}
                      className="w-full p-1.5 border border-emerald-300 rounded-lg bg-white"
                    >
                      <option value="cash">Cash (Farm Gate)</option>
                      <option value="airtel_money">Airtel Money</option>
                      <option value="tnm_mpamba">TNM Mpamba</option>
                      <option value="bank_transfer">Bank Transfer</option>
                    </select>
                  </div>
                </div>

                <div className="flex justify-between text-xs font-semibold">
                  <span>Balance Due:</span>
                  <span className={total - paymentReceived > 0 ? 'text-amber-800' : 'text-emerald-700'}>
                    {formatMWK(Math.max(0, total - paymentReceived))}
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Delivery / Handover</label>
                  <select
                    value={deliveryStatus}
                    onChange={(e) => setDeliveryStatus(e.target.value as any)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="collected">Collected at Farm</option>
                    <option value="dispatched">Dispatched on Delivery</option>
                    <option value="delivered">Delivered to Customer</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Driver / Salesperson</label>
                  <input
                    type="text"
                    value={driver}
                    onChange={(e) => setDriver(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowSaleModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Sale & Generate Invoice
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Receipt Modal */}
      {selectedReceiptSale && (
        <ReceiptModal sale={selectedReceiptSale} onClose={() => setSelectedReceiptSale(null)} />
      )}

      {/* Poultry Price Matrix & Rate Card Modal */}
      <PoultryPriceCalculatorModal
        isOpen={showPriceCalcModal}
        onClose={() => setShowPriceCalcModal(false)}
        onSelectPrice={(price, speciesName, weeks, qty) => {
          setProductCategory('live_birds');
          setUnit('birds');
          setUnitPrice(price);
          setQuantity(qty);
          setDescription(`${speciesName} (${weeks === 0 ? 'Day-Old DOC' : `Week ${weeks}`})`);
          setPaymentReceived(qty * price - discount);
        }}
      />
    </div>
  );
};
