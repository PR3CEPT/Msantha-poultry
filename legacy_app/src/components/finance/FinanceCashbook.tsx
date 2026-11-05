import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { Expense, ExpenseCategory, PaymentMethod } from '../../types';
import { formatDate, formatMWK } from '../../utils/i18n';
import {
  DollarSign,
  Plus,
  TrendingUp,
  TrendingDown,
  ArrowUpRight,
  ArrowDownRight,
  Calendar,
  Search,
  Wallet,
  Smartphone,
  CreditCard,
} from 'lucide-react';

export const FinanceCashbook: React.FC = () => {
  const { db, currentUser, canAccessFinance, addExpense } = useFarmData();

  const [activeTab, setActiveTab] = useState<'cashbook' | 'summary' | 'expenses'>('cashbook');
  const [showExpenseModal, setShowExpenseModal] = useState(false);
  const [categoryFilter, setCategoryFilter] = useState('all');

  // Expense form
  const today = new Date().toISOString().split('T')[0];
  const [expDate, setExpDate] = useState(today);
  const [expCategory, setExpCategory] = useState<ExpenseCategory>('feed');
  const [expDesc, setExpDesc] = useState('');
  const [expAmount, setExpAmount] = useState(40000);
  const [expPayMethod, setExpPayMethod] = useState<PaymentMethod>('cash');
  const [expPayee, setExpPayee] = useState('');
  const [expRef, setExpRef] = useState('');
  const [expNotes, setExpNotes] = useState('');

  const handleCreateExpense = (e: React.FormEvent) => {
    e.preventDefault();
    const expenseNumber = `EXP-2026-${String(db.expenses.length + 56).padStart(3, '0')}`;
    addExpense({
      expenseNumber,
      date: expDate,
      category: expCategory,
      description: expDesc || `${expCategory.replace('_', ' ')} expense`,
      amount: Number(expAmount),
      paymentMethod: expPayMethod,
      recipientOrPayee: expPayee || 'Liwonde Supplier',
      referenceNumber: expRef,
      recordedBy: currentUser.name,
      notes: expNotes,
    });
    setShowExpenseModal(false);
  };

  // Compile Cashbook Ledger Items (All cash income + all cash expenses)
  const incomeItems = [
    ...db.sales.map((s) => ({
      id: s.id,
      date: s.date,
      type: 'income' as const,
      category: s.productCategory.replace('_', ' '),
      description: `${s.customerName} - ${s.description}`,
      amount: s.paymentReceived,
      method: s.paymentMethod,
      ref: s.invoiceNumber,
      recordedBy: s.salesperson,
    })),
    ...db.customerIncubations.flatMap((b) =>
      b.payments.map((p) => ({
        id: p.id,
        date: p.date,
        type: 'income' as const,
        category: 'incubation service',
        description: `Customer Incubation: ${b.customerName} (${b.batchCode})`,
        amount: p.amount,
        method: p.method,
        ref: p.receiptNumber,
        recordedBy: p.recordedBy,
      }))
    ),
  ];

  const expenseItems = db.expenses.map((e) => ({
    id: e.id,
    date: e.date,
    type: 'expense' as const,
    category: e.category.replace('_', ' '),
    description: `${e.recipientOrPayee}: ${e.description}`,
    amount: e.amount,
    method: e.paymentMethod,
    ref: e.referenceNumber || e.expenseNumber,
    recordedBy: e.recordedBy,
  }));

  const combinedLedger = [...incomeItems, ...expenseItems].sort(
    (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()
  );

  const totalCashIncome = incomeItems.reduce((sum, item) => sum + item.amount, 0);
  const totalCashExpenses = expenseItems.reduce((sum, item) => sum + item.amount, 0);
  const netEstimatedCashflow = totalCashIncome - totalCashExpenses;

  // Breakdown by payment methods
  const paymentMethodTotals = combinedLedger.reduce((acc, item) => {
    const key = item.method || 'cash';
    if (!acc[key]) acc[key] = { in: 0, out: 0 };
    if (item.type === 'income') acc[key].in += item.amount;
    else acc[key].out += item.amount;
    return acc;
  }, {} as Record<string, { in: number; out: number }>);

  if (!canAccessFinance) {
    return (
      <div className="bg-white rounded-2xl border border-slate-200 p-8 text-center max-w-md mx-auto my-12 shadow-sm">
        <DollarSign className="w-12 h-12 text-slate-400 mx-auto mb-3" />
        <h3 className="text-base font-bold text-slate-900">Restricted Module</h3>
        <p className="text-xs text-slate-500 mt-1">
          Financial accounts and cashbook are restricted to Isaac Chipeta (Managing Director) and Sikizwayo Chipeta (Marketing Manager).
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Farm Cashbook & Financials</h2>
          <p className="text-xs text-slate-500">
            Official cashbook in Malawi Kwacha (MWK) • Income from eggs & incubation, farm operating expenditures.
          </p>
        </div>

        <button
          onClick={() => setShowExpenseModal(true)}
          className="flex items-center justify-center gap-1.5 px-3.5 py-2 bg-rose-800 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
        >
          <Plus className="w-4 h-4" />
          <span>Record Expense</span>
        </button>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <div className="flex justify-between items-center text-xs text-slate-500">
            <span className="font-bold uppercase tracking-wider text-[10px]">Total Cash Received</span>
            <TrendingUp className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl font-black text-emerald-800 mt-1">
            {formatMWK(totalCashIncome)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            From egg sales, live birds & incubation fees
          </p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <div className="flex justify-between items-center text-xs text-slate-500">
            <span className="font-bold uppercase tracking-wider text-[10px]">Total Farm Expenses</span>
            <TrendingDown className="w-4 h-4 text-rose-600" />
          </div>
          <div className="text-2xl font-black text-rose-900 mt-1">
            {formatMWK(totalCashExpenses)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            Feed purchases, ESCOM tokens, packaging & labour
          </p>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
          <div className="flex justify-between items-center text-xs text-slate-500">
            <span className="font-bold uppercase tracking-wider text-[10px]">Estimated Net Position</span>
            <DollarSign className="w-4 h-4 text-slate-600" />
          </div>
          <div
            className={`text-2xl font-black mt-1 ${
              netEstimatedCashflow >= 0 ? 'text-emerald-950' : 'text-rose-900'
            }`}
          >
            {formatMWK(netEstimatedCashflow)}
          </div>
          <p className="text-[11px] text-slate-500 mt-1">
            Operational cash surplus / deficit
          </p>
        </div>
      </div>

      {/* Payment Channels Grid (Airtel, TNM, Cash) */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        {['cash', 'airtel_money', 'tnm_mpamba', 'bank_transfer'].map((method) => {
          const stats = paymentMethodTotals[method] || { in: 0, out: 0 };
          const label =
            method === 'airtel_money'
              ? 'Airtel Money'
              : method === 'tnm_mpamba'
              ? 'TNM Mpamba'
              : method === 'bank_transfer'
              ? 'Bank Transfer'
              : 'Cash at Gate';

          return (
            <div key={method} className="bg-slate-50 p-3 rounded-xl border border-slate-200">
              <span className="font-bold text-slate-800">{label}</span>
              <div className="mt-2 space-y-0.5 text-[11px]">
                <div className="flex justify-between text-emerald-800 font-medium">
                  <span>In:</span>
                  <span>{formatMWK(stats.in)}</span>
                </div>
                <div className="flex justify-between text-rose-800 font-medium">
                  <span>Out:</span>
                  <span>{formatMWK(stats.out)}</span>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Ledger Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="p-3.5 border-b border-slate-100 flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-900">Farm Cashbook Ledger</h3>
          <span className="text-xs text-slate-500">{combinedLedger.length} entries chronologically</span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th className="p-3">Date</th>
                <th className="p-3">Type</th>
                <th className="p-3">Category</th>
                <th className="p-3">Description</th>
                <th className="p-3 text-right">Inflow (MK)</th>
                <th className="p-3 text-right">Outflow (MK)</th>
                <th className="p-3">Method</th>
                <th className="p-3">Ref</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {combinedLedger.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition">
                  <td className="p-3 whitespace-nowrap text-slate-800">{formatDate(item.date)}</td>
                  <td className="p-3">
                    <span
                      className={`text-[10px] font-bold px-2 py-0.5 rounded uppercase ${
                        item.type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                      }`}
                    >
                      {item.type}
                    </span>
                  </td>
                  <td className="p-3 capitalize font-medium text-slate-800">{item.category}</td>
                  <td className="p-3 text-slate-700">{item.description}</td>
                  <td className="p-3 text-right font-black text-emerald-800">
                    {item.type === 'income' ? formatMWK(item.amount) : '-'}
                  </td>
                  <td className="p-3 text-right font-black text-rose-800">
                    {item.type === 'expense' ? formatMWK(item.amount) : '-'}
                  </td>
                  <td className="p-3 uppercase text-[10px] text-slate-600 font-semibold">
                    {item.method.replace('_', ' ')}
                  </td>
                  <td className="p-3 font-mono text-[10px] text-slate-400">{item.ref}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Record Expense Modal */}
      {showExpenseModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3">
          <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 border border-slate-200 animate-in fade-in my-auto max-h-[92vh] overflow-y-auto">
            <h3 className="text-base font-bold text-slate-900 mb-1">Record Farm Expenditure</h3>
            <p className="text-xs text-slate-500 mb-4">
              Enter details for cash or mobile money farm expenses.
            </p>

            <form onSubmit={handleCreateExpense} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Date</label>
                  <input
                    type="date"
                    value={expDate}
                    onChange={(e) => setExpDate(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Category</label>
                  <select
                    value={expCategory}
                    onChange={(e) => setExpCategory(e.target.value as ExpenseCategory)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="feed">Feed purchase</option>
                    <option value="electricity">Electricity / ESCOM tokens</option>
                    <option value="labour_salaries">Farm labour / Salaries</option>
                    <option value="packaging">Packaging (Trays / Boxes)</option>
                    <option value="transport_fuel">Transport & Fuel</option>
                    <option value="vaccines_medicines">Medicines & Vaccines</option>
                    <option value="equipment_repairs">Equipment / Repairs</option>
                    <option value="water">Water supply</option>
                    <option value="other">Other overheads</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-slate-700 mb-1">Description</label>
                <input
                  type="text"
                  placeholder="e.g. 10 bags Layer Mash, 200 kWh ESCOM units"
                  value={expDesc}
                  onChange={(e) => setExpDesc(e.target.value)}
                  className="w-full p-2 border border-slate-300 rounded-lg"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-rose-800 mb-1">Amount (MK)</label>
                  <input
                    type="number"
                    min="1"
                    value={expAmount}
                    onChange={(e) => setExpAmount(Number(e.target.value))}
                    className="w-full p-2 border border-rose-300 rounded-lg font-bold text-rose-950 text-base"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Payment Method</label>
                  <select
                    value={expPayMethod}
                    onChange={(e) => setExpPayMethod(e.target.value as PaymentMethod)}
                    className="w-full p-2 border border-slate-300 rounded-lg bg-white"
                  >
                    <option value="cash">Cash</option>
                    <option value="airtel_money">Airtel Money</option>
                    <option value="tnm_mpamba">TNM Mpamba</option>
                    <option value="bank_transfer">Bank Transfer</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Payee / Merchant</label>
                  <input
                    type="text"
                    placeholder="Merchant or supplier name"
                    value={expPayee}
                    onChange={(e) => setExpPayee(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-slate-700 mb-1">Reference / Token #</label>
                  <input
                    type="text"
                    placeholder="Ref or receipt no"
                    value={expRef}
                    onChange={(e) => setExpRef(e.target.value)}
                    className="w-full p-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setShowExpenseModal(false)}
                  className="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-rose-800 hover:bg-rose-700 text-white font-semibold rounded-xl"
                >
                  Record Expense
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
