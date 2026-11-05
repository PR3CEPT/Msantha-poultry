import React from 'react';
import { CustomerIncubationBatch, Sale } from '../../types';
import { useFarmData } from '../../hooks/useFarmData';
import { formatDate, formatMWK } from '../../utils/i18n';
import { Printer, Share2, X, CheckCircle, Phone } from 'lucide-react';

interface ReceiptModalProps {
  incubationBatch?: CustomerIncubationBatch;
  sale?: Sale;
  onClose: () => void;
}

export const ReceiptModal: React.FC<ReceiptModalProps> = ({ incubationBatch, sale, onClose }) => {
  const { settings } = useFarmData();

  if (!incubationBatch && !sale) return null;

  const handlePrint = () => {
    window.print();
  };

  // Build clean WhatsApp message for Malawian customers
  const handleWhatsAppShare = () => {
    let text = '';
    if (incubationBatch) {
      text = `*MSANTHA POULTRY FARM — INCUBATION RECEIPT*\n` +
        `📍 Location: Liwonde, Malawi\n` +
        `📞 Contact: ${settings.phoneContact}\n` +
        `----------------------------------------\n` +
        `*Receipt / Batch:* ${incubationBatch.batchCode}\n` +
        `*Customer:* ${incubationBatch.customerName}\n` +
        `*Phone:* ${incubationBatch.customerPhone}\n` +
        `*Species / Breed:* ${incubationBatch.species} ${incubationBatch.breed ? `(${incubationBatch.breed})` : ''}\n` +
        `*Eggs Accepted:* ${incubationBatch.eggsAccepted} eggs\n` +
        `*Setting Date:* ${formatDate(incubationBatch.dateSet)}\n` +
        `*Expected Hatch Date:* ${formatDate(incubationBatch.expectedHatchDate)}\n` +
        `*Collection Date:* ${formatDate(incubationBatch.plannedCollectionDate)}\n` +
        `*Status:* ${incubationBatch.status.toUpperCase()}\n` +
        `----------------------------------------\n` +
        `*Total Service Fee:* ${formatMWK(incubationBatch.totalCharge)}\n` +
        `*Deposit / Paid:* ${formatMWK(incubationBatch.amountPaid)}\n` +
        `*Balance Due:* ${formatMWK(incubationBatch.balanceDue)}\n` +
        `----------------------------------------\n` +
        `Thank you for trusting Msantha Poultry Farm! Please bring this message or receipt during collection.`;
    } else if (sale) {
      text = `*MSANTHA POULTRY FARM — SALES RECEIPT*\n` +
        `📍 Location: Liwonde, Malawi\n` +
        `📞 Contact: ${settings.phoneContact}\n` +
        `----------------------------------------\n` +
        `*Invoice:* ${sale.invoiceNumber}\n` +
        `*Date:* ${formatDate(sale.date)}\n` +
        `*Customer:* ${sale.customerName}\n` +
        `*Item:* ${sale.description}\n` +
        `*Quantity:* ${sale.quantity} ${sale.unit}\n` +
        `*Total Amount:* ${formatMWK(sale.total)}\n` +
        `*Amount Paid:* ${formatMWK(sale.paymentReceived)}\n` +
        `*Balance Due:* ${formatMWK(sale.balanceDue)}\n` +
        `*Salesperson:* ${sale.salesperson}\n` +
        `----------------------------------------\n` +
        `Zikomo kwambiri! Thank you for your business.`;
    }

    const encoded = encodeURIComponent(text);
    const url = `https://api.whatsapp.com/send?text=${encoded}`;
    window.open(url, '_blank');
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
      <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 my-auto">
        {/* Modal Top Actions */}
        <div className="bg-slate-900 text-white px-4 py-3 flex items-center justify-between no-print">
          <div className="flex items-center gap-2">
            <span className="text-xs font-bold uppercase tracking-wider text-emerald-400">Official Farm Receipt</span>
          </div>
          <div className="flex items-center gap-1.5">
            <button
              onClick={handleWhatsAppShare}
              className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium transition"
              title="Share receipt via WhatsApp"
            >
              <Share2 className="w-3.5 h-3.5" />
              <span>WhatsApp</span>
            </button>
            <button
              onClick={handlePrint}
              className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium transition"
              title="Print Receipt"
            >
              <Printer className="w-3.5 h-3.5" />
              <span>Print</span>
            </button>
            <button
              onClick={onClose}
              className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 ml-1"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Printable Receipt Body */}
        <div className="p-6 text-slate-800 text-xs printable-receipt bg-white" id="printable-receipt">
          {/* Farm Header */}
          <div className="text-center pb-4 border-b border-dashed border-slate-300">
            <div className="w-12 h-12 mx-auto mb-2">
              <img src="/icon.svg" alt="Msantha Poultry Farm" className="w-full h-full object-contain" />
            </div>
            <h2 className="text-lg font-black tracking-tight text-emerald-950 uppercase">
              {settings.farmName}
            </h2>
            <p className="font-semibold text-slate-700">{settings.location}</p>
            <p className="text-[11px] text-slate-500">
              Managing Director: {settings.managingDirector} • Marketing: {settings.marketingManager}
            </p>
            <p className="text-[11px] text-slate-600 mt-0.5">
              Phone: {settings.phoneContact}
            </p>
          </div>

          {/* Incubation Receipt Content */}
          {incubationBatch && (
            <div className="mt-4 space-y-3">
              <div className="flex justify-between items-center text-xs">
                <div>
                  <span className="text-slate-500">Batch Code:</span>{' '}
                  <span className="font-bold text-slate-900">{incubationBatch.batchCode}</span>
                </div>
                <div>
                  <span className="text-slate-500">Date Set:</span>{' '}
                  <span className="font-medium">{formatDate(incubationBatch.dateSet)}</span>
                </div>
              </div>

              <div className="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p className="text-[11px] uppercase tracking-wider font-semibold text-slate-500">Customer Details</p>
                <p className="font-bold text-slate-900 text-sm mt-0.5">{incubationBatch.customerName}</p>
                <p className="text-slate-600">{incubationBatch.customerPhone}</p>
                {incubationBatch.customerVillage && (
                  <p className="text-slate-500 text-[11px]">{incubationBatch.customerVillage}</p>
                )}
              </div>

              <div className="border border-slate-200 rounded-lg overflow-hidden">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-100 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                      <th className="p-2">Description</th>
                      <th className="p-2 text-right">Details</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    <tr>
                      <td className="p-2 text-slate-600">Poultry Species / Breed</td>
                      <td className="p-2 text-right font-medium text-slate-900">
                        {incubationBatch.species} {incubationBatch.breed ? `(${incubationBatch.breed})` : ''}
                      </td>
                    </tr>
                    <tr>
                      <td className="p-2 text-slate-600">Eggs Received / Accepted</td>
                      <td className="p-2 text-right font-medium text-slate-900">
                        {incubationBatch.eggsReceived} / <span className="font-bold">{incubationBatch.eggsAccepted}</span>
                      </td>
                    </tr>
                    {incubationBatch.eggsRejectedAtIntake > 0 && (
                      <tr>
                        <td className="p-2 text-rose-700">Rejected at Intake</td>
                        <td className="p-2 text-right text-rose-700 font-medium">
                          {incubationBatch.eggsRejectedAtIntake} ({incubationBatch.rejectionReason || 'Damaged'})
                        </td>
                      </tr>
                    )}
                    <tr>
                      <td className="p-2 text-slate-600">Incubator Assigned</td>
                      <td className="p-2 text-right font-medium text-slate-900">{incubationBatch.incubatorId}</td>
                    </tr>
                    <tr>
                      <td className="p-2 text-slate-600">Expected Hatch Date</td>
                      <td className="p-2 text-right font-bold text-emerald-800">
                        {formatDate(incubationBatch.expectedHatchDate)}
                      </td>
                    </tr>
                    <tr>
                      <td className="p-2 text-slate-600">Collection Date</td>
                      <td className="p-2 text-right font-semibold text-slate-900">
                        {formatDate(incubationBatch.plannedCollectionDate)}
                      </td>
                    </tr>
                    {incubationBatch.chicksHatched !== undefined && (
                      <tr className="bg-emerald-50">
                        <td className="p-2 font-bold text-emerald-900">Chicks Hatched / Delivered</td>
                        <td className="p-2 text-right font-bold text-emerald-900">
                          {incubationBatch.chicksHatched} chicks
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              {/* Financial Breakdown */}
              <div className="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-1.5">
                <div className="flex justify-between">
                  <span className="text-slate-600">Total Charge:</span>
                  <span className="font-semibold text-slate-900">{formatMWK(incubationBatch.totalCharge)}</span>
                </div>
                <div className="flex justify-between text-emerald-700">
                  <span>Amount Paid to Date:</span>
                  <span className="font-semibold">-{formatMWK(incubationBatch.amountPaid)}</span>
                </div>
                <div className="flex justify-between pt-1.5 border-t border-slate-200 text-sm font-bold">
                  <span className="text-slate-900">Balance Due:</span>
                  <span className={incubationBatch.balanceDue > 0 ? 'text-amber-700 font-extrabold' : 'text-emerald-700'}>
                    {formatMWK(incubationBatch.balanceDue)}
                  </span>
                </div>
              </div>

              {/* Payment records history */}
              {incubationBatch.payments.length > 0 && (
                <div className="text-[11px]">
                  <p className="font-semibold text-slate-700 mb-1">Payment Transactions:</p>
                  <div className="space-y-1">
                    {incubationBatch.payments.map((p, idx) => (
                      <div key={p.id || idx} className="flex justify-between text-slate-600 border-b border-slate-100 pb-0.5">
                        <span>{formatDate(p.date)} ({p.method.replace('_', ' ').toUpperCase()}) - {p.receiptNumber}</span>
                        <span className="font-medium text-slate-900">{formatMWK(p.amount)}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Sales Receipt Content */}
          {sale && (
            <div className="mt-4 space-y-3">
              <div className="flex justify-between items-center text-xs">
                <div>
                  <span className="text-slate-500">Invoice:</span>{' '}
                  <span className="font-bold text-slate-900">{sale.invoiceNumber}</span>
                </div>
                <div>
                  <span className="text-slate-500">Date:</span>{' '}
                  <span className="font-medium">{formatDate(sale.date)}</span>
                </div>
              </div>

              <div className="bg-slate-50 p-3 rounded-lg border border-slate-200">
                <p className="text-[11px] uppercase tracking-wider font-semibold text-slate-500">Customer</p>
                <p className="font-bold text-slate-900 text-sm mt-0.5">{sale.customerName}</p>
                {sale.customerPhone && <p className="text-slate-600">{sale.customerPhone}</p>}
              </div>

              <div className="border border-slate-200 rounded-lg p-3 space-y-2">
                <div className="flex justify-between font-semibold text-slate-900">
                  <span>{sale.description}</span>
                  <span>{formatMWK(sale.total)}</span>
                </div>
                <div className="text-slate-500 text-[11px]">
                  Quantity: {sale.quantity} {sale.unit} @ {formatMWK(sale.unitPrice)} each
                </div>
              </div>

              <div className="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-1.5">
                <div className="flex justify-between">
                  <span className="text-slate-600">Total:</span>
                  <span className="font-semibold text-slate-900">{formatMWK(sale.total)}</span>
                </div>
                <div className="flex justify-between text-emerald-700">
                  <span>Paid ({sale.paymentMethod.replace('_', ' ').toUpperCase()}):</span>
                  <span className="font-semibold">{formatMWK(sale.paymentReceived)}</span>
                </div>
                <div className="flex justify-between pt-1.5 border-t border-slate-200 text-sm font-bold">
                  <span>Balance Due:</span>
                  <span className={sale.balanceDue > 0 ? 'text-amber-700' : 'text-emerald-700'}>
                    {formatMWK(sale.balanceDue)}
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* Footer Terms */}
          <div className="mt-6 pt-3 border-t border-slate-200 text-center text-[10px] text-slate-500 space-y-1">
            <p className="font-medium text-slate-700">
              Notice: Chicks must be collected promptly within 24 hours of notification.
            </p>
            <p>Generated by Msantha Poultry Farm Manager • Liwonde, Malawi</p>
          </div>
        </div>

        {/* Modal Bottom Footer */}
        <div className="bg-slate-50 px-4 py-3 border-t border-slate-200 flex justify-end no-print">
          <button
            onClick={onClose}
            className="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg text-xs"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};
