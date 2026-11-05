import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { Customer } from '../../types';
import { formatDate, formatMWK } from '../../utils/i18n';
import { Users, Plus, Phone, MapPin, Search, DollarSign, MessageCircle } from 'lucide-react';
import { saveDatabase } from '../../services/storage';

export const CustomerDirectory: React.FC = () => {
  const { db } = useFarmData();
  const [search, setSearch] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);

  // New Customer Form
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('+265 ');
  const [village, setVillage] = useState('Liwonde');
  const [category, setCategory] = useState<Customer['category']>('commercial_farmer');
  const [notes, setNotes] = useState('');

  const handleAddCustomer = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name) return;

    const newCust: Customer = {
      id: `cust_${Date.now()}`,
      customerCode: `CUST-${String(db.customers.length + 5).padStart(3, '0')}`,
      name,
      phone,
      addressVillage: village,
      category,
      notes,
      createdAt: Date.now(),
    };

    saveDatabase({ ...db, customers: [newCust, ...db.customers] });
    setShowAddModal(false);
    setName('');
    setNotes('');
  };

  const filtered = db.customers.filter((c) => {
    return (
      c.name.toLowerCase().includes(search.toLowerCase()) ||
      c.phone.includes(search) ||
      c.addressVillage.toLowerCase().includes(search.toLowerCase())
    );
  });

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Commercial Customer Directory</h2>
          <p className="text-xs text-slate-500">
            Farming clients, egg wholesale buyers, restaurants, and commercial incubation customers across Liwonde & Machinga.
          </p>
        </div>

        <button
          onClick={() => setShowAddModal(true)}
          className="flex items-center justify-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
        >
          <Plus className="w-4 h-4" />
          <span>Add New Customer</span>
        </button>
      </div>

      {/* Search */}
      <div className="bg-white p-3 rounded-xl border border-slate-200 flex items-center justify-between">
        <div className="relative w-full sm:w-72">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input
            type="text"
            placeholder="Search customer by name, phone, village..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg bg-slate-50 focus:bg-white focus:outline-hidden"
          />
        </div>
        <span className="text-xs text-slate-500">{filtered.length} customers registered</span>
      </div>

      {/* Customer Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filtered.map((cust) => {
          // Calculate customer stats
          const custSales = db.sales.filter((s) => s.customerId === cust.id || s.customerName.toLowerCase() === cust.name.toLowerCase());
          const custIncubations = db.customerIncubations.filter((b) => b.customerId === cust.id || b.customerName.toLowerCase() === cust.name.toLowerCase());

          const totalSpend =
            custSales.reduce((sum, s) => sum + s.total, 0) +
            custIncubations.reduce((sum, b) => sum + b.totalCharge, 0);

          const balance =
            custSales.reduce((sum, s) => sum + s.balanceDue, 0) +
            custIncubations.reduce((sum, b) => sum + b.balanceDue, 0);

          const waUrl = `https://wa.me/${cust.phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(
            `Moni a ${cust.name}, greetings from Msantha Poultry Farm in Liwonde!`
          )}`;

          return (
            <div key={cust.id} className="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between hover:border-slate-300 transition">
              <div>
                <div className="flex justify-between items-center text-xs mb-1">
                  <span className="font-mono text-[11px] font-bold text-slate-500">{cust.customerCode}</span>
                  <span className="text-[10px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded capitalize">
                    {cust.category.replace('_', ' ')}
                  </span>
                </div>

                <h3 className="font-bold text-slate-900 text-base leading-snug">{cust.name}</h3>

                <div className="space-y-1 mt-2 text-xs text-slate-600">
                  <p className="flex items-center gap-1.5">
                    <Phone className="w-3.5 h-3.5 text-slate-400" />
                    <span>{cust.phone}</span>
                  </p>
                  <p className="flex items-center gap-1.5">
                    <MapPin className="w-3.5 h-3.5 text-slate-400" />
                    <span>{cust.addressVillage}</span>
                  </p>
                </div>

                {/* Financial Summary */}
                <div className="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 text-xs">
                  <div>
                    <span className="text-[10px] text-slate-400 uppercase font-semibold">Total Business</span>
                    <p className="font-bold text-slate-900 mt-0.5">{formatMWK(totalSpend)}</p>
                    <p className="text-[10px] text-slate-400">
                      {custIncubations.length} batches · {custSales.length} sales
                    </p>
                  </div>
                  <div>
                    <span className="text-[10px] text-slate-400 uppercase font-semibold">Balance Due</span>
                    <p className={`font-black mt-0.5 ${balance > 0 ? 'text-amber-800' : 'text-emerald-700'}`}>
                      {formatMWK(balance)}
                    </p>
                    {balance > 0 && <span className="text-[10px] text-amber-700 font-semibold">Pending</span>}
                  </div>
                </div>

                {cust.notes && (
                  <p className="mt-2.5 text-[11px] text-slate-500 italic bg-slate-50 p-2 rounded">
                    {cust.notes}
                  </p>
                )}
              </div>

              {/* Contact Button */}
              <div className="mt-4 pt-3 border-t border-slate-100 flex gap-2">
                <a
                  href={`tel:${cust.phone}`}
                  className="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition"
                >
                  <Phone className="w-3.5 h-3.5" />
                  <span>Call</span>
                </a>
                <a
                  href={waUrl}
                  target="_blank"
                  rel="noreferrer"
                  className="flex-1 py-1.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition"
                >
                  <MessageCircle className="w-3.5 h-3.5 text-emerald-700" />
                  <span>WhatsApp</span>
                </a>
              </div>
            </div>
          );
        })}
      </div>

      {/* Add Customer Modal */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Add Customer</h3>
            <p className="text-xs text-slate-500 mb-4">Register client for incubation or sales.</p>

            <form onSubmit={handleAddCustomer} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-slate-700 mb-1">Customer Full Name</label>
                <input
                  type="text"
                  placeholder="e.g. Kondwani Banda"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Phone Number</label>
                  <input
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Category</label>
                  <select
                    value={category}
                    onChange={(e) => setCategory(e.target.value as any)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="commercial_farmer">Commercial Farmer</option>
                    <option value="individual">Smallholder / Individual</option>
                    <option value="vendor">Market Vendor</option>
                    <option value="restaurant">Restaurant / Lodge</option>
                    <option value="wholesale">Wholesale Buyer</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Village / Address</label>
                <input
                  type="text"
                  value={village}
                  onChange={(e) => setVillage(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div>
                <label className="block font-medium text-slate-700 mb-1">Notes</label>
                <input
                  type="text"
                  placeholder="e.g. Prefers local chicken eggs, weekly delivery"
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold rounded-xl"
                >
                  Save Customer
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
