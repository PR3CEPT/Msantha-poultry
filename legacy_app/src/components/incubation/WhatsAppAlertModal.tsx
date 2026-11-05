import React, { useState } from 'react';
import { CustomerIncubationBatch, FarmSettings } from '../../types';
import { formatDate, formatMWK } from '../../utils/i18n';
import { Share2, Phone, X, Check, Copy, MessageSquare, AlertCircle, Sparkles } from 'lucide-react';

export type WhatsAppAlertType = 'setting_confirmation' | 'hatching_collection';

interface WhatsAppAlertModalProps {
  batch: CustomerIncubationBatch;
  alertType: WhatsAppAlertType;
  settings: FarmSettings;
  onClose: () => void;
}

export const WhatsAppAlertModal: React.FC<WhatsAppAlertModalProps> = ({
  batch,
  alertType,
  settings,
  onClose,
}) => {
  const [copied, setCopied] = useState(false);
  const [customChicksHatched, setCustomChicksHatched] = useState(
    batch.chicksHatched || Math.round(batch.eggsAccepted * 0.85)
  );

  // Clean customer phone number for WhatsApp URL (e.g. +265 991 234 567 -> 265991234567)
  const cleanPhone = batch.customerPhone.replace(/[^0-9]/g, '');

  // 1. Setting Confirmation Message
  const getSettingMessage = (): string => {
    return (
      `*MSANTHA POULTRY FARM — INCUBATION SETTING CONFIRMATION*\n` +
      `📍 Location: Liwonde, Malawi\n` +
      `📞 Contacts: ${settings.phoneContact}\n` +
      `----------------------------------------\n` +
      `Dear *${batch.customerName}*,\n\n` +
      `We have successfully prepared and set your eggs into our solar-powered digital incubator.\n\n` +
      `📋 *Batch Code:* ${batch.batchCode}\n` +
      `🥚 *Species / Breed:* ${batch.species} ${batch.breed ? `(${batch.breed})` : ''}\n` +
      `📥 *Eggs Received:* ${batch.eggsReceived} eggs\n` +
      `✅ *Eggs Accepted for Incubation:* ${batch.eggsAccepted} eggs\n` +
      (batch.eggsRejectedAtIntake > 0
        ? `⚠️ *Rejected at Intake:* ${batch.eggsRejectedAtIntake} (${batch.rejectionReason || 'Damaged / cracked'})\n`
        : '') +
      `🌡️ *Incubator Unit:* ${batch.incubatorId}\n` +
      `📅 *Date Set:* ${formatDate(batch.dateSet)}\n` +
      `🐣 *Expected Hatch Date:* ${formatDate(batch.expectedHatchDate)}\n` +
      `📦 *Planned Collection Date:* ${formatDate(batch.plannedCollectionDate)}\n` +
      `----------------------------------------\n` +
      `💰 *Total Service Fee:* ${formatMWK(batch.totalCharge)}\n` +
      `💵 *Deposit Paid:* ${formatMWK(batch.amountPaid)}\n` +
      `⏳ *Balance Due on Collection:* ${formatMWK(batch.balanceDue)}\n` +
      `----------------------------------------\n` +
      `We will notify you on the 7th day after candling and right when the chicks begin hatching.\n\n` +
      `Zikomo kwambiri!\n` +
      `Managing Director: ${settings.managingDirector}\n` +
      `Distribution & Marketing: ${settings.marketingManager}`
    );
  };

  // 2. Hatching & Chick Collection Alert Message
  const getHatchingMessage = (): string => {
    const chicksCount = customChicksHatched;
    return (
      `*MSANTHA POULTRY FARM — HATCH READY & CHICK COLLECTION*\n` +
      `📍 Location: Liwonde, Malawi\n` +
      `📞 Contacts: ${settings.phoneContact}\n` +
      `----------------------------------------\n` +
      `Moni a *${batch.customerName}*!\n\n` +
      `🎉 Good news! Your *${batch.species}* eggs in Batch *${batch.batchCode}* have hatched successfully and the chicks are lively, dry, and ready for pickup!\n\n` +
      `🐣 *Chicks Hatched Alive:* ${chicksCount} healthy chicks\n` +
      `🥚 *Original Eggs Set:* ${batch.eggsAccepted} eggs\n` +
      `📅 *Collection Date:* TODAY / ASAP (${formatDate(new Date().toISOString().split('T')[0])})\n` +
      `💰 *Remaining Balance Due:* ${formatMWK(batch.balanceDue)}\n` +
      `----------------------------------------\n` +
      `*IMPORTANT COLLECTION INSTRUCTIONS:*\n` +
      `1. Please come promptly to collect your chicks so they can be brooded with warm heat and start drinking electrolyte water.\n` +
      `2. Bring clean, well-ventilated cardboard chick boxes or baskets lined with dry grass or clean newspaper.\n` +
      `3. Balance payment accepted via Airtel Money, TNM Mpamba, or Cash at the farm gate.\n` +
      `----------------------------------------\n` +
      `Farm Address: Msantha Poultry Farm, Liwonde\n` +
      `Please call or reply to confirm when you are arriving!\n\n` +
      `Managing Director: ${settings.managingDirector}\n` +
      `Distribution & Marketing Manager: ${settings.marketingManager}`
    );
  };

  const currentMessage = alertType === 'setting_confirmation' ? getSettingMessage() : getHatchingMessage();

  const whatsAppUrl = cleanPhone
    ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(currentMessage)}`
    : `https://api.whatsapp.com/send?text=${encodeURIComponent(currentMessage)}`;

  const handleCopy = () => {
    navigator.clipboard.writeText(currentMessage);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-3 overflow-y-auto">
      <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 my-auto max-h-[94vh] flex flex-col">
        {/* Modal Top Header */}
        <div className="bg-emerald-950 text-white px-4 py-3.5 flex items-center justify-between shrink-0">
          <div className="flex items-center gap-2">
            <div className="p-1.5 rounded-lg bg-emerald-800 text-emerald-300">
              <Share2 className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-sm font-bold tracking-tight">
                {alertType === 'setting_confirmation'
                  ? 'WhatsApp Setting Confirmation Alert'
                  : 'WhatsApp Hatch & Chick Collection Alert'}
              </h3>
              <p className="text-[11px] text-emerald-300">
                Direct client notification for Msantha Poultry Farm
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-lg text-emerald-300 hover:text-white hover:bg-emerald-900 transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Customer Quick Contact Banner */}
        <div className="bg-emerald-50/80 px-4 py-3 border-b border-emerald-100 flex items-center justify-between shrink-0 text-xs">
          <div>
            <span className="text-[10px] uppercase font-bold text-emerald-800">Customer Contact</span>
            <p className="font-bold text-slate-900 text-sm mt-0.5">{batch.customerName}</p>
            <p className="text-slate-600 font-medium">{batch.customerPhone} · {batch.customerVillage || 'Liwonde'}</p>
          </div>
          <div className="flex items-center gap-1.5">
            <a
              href={`tel:${batch.customerPhone}`}
              className="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold flex items-center gap-1 shadow-xs transition"
            >
              <Phone className="w-3.5 h-3.5 text-emerald-700" />
              <span>Call</span>
            </a>
            <a
              href={whatsAppUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white font-bold flex items-center gap-1.5 shadow-xs transition"
            >
              <Share2 className="w-3.5 h-3.5" />
              <span>WhatsApp</span>
            </a>
          </div>
        </div>

        {/* Scrollable Preview Area */}
        <div className="p-4 overflow-y-auto space-y-3 text-xs flex-1">
          {alertType === 'hatching_collection' && (
            <div className="bg-amber-50 border border-amber-200 p-2.5 rounded-xl space-y-1">
              <label className="block text-[11px] font-bold text-amber-950">
                Verify Chicks Hatched Count (will update message automatically):
              </label>
              <div className="flex items-center gap-2">
                <input
                  type="number"
                  min="1"
                  value={customChicksHatched}
                  onChange={(e) => setCustomChicksHatched(Number(e.target.value))}
                  className="w-24 p-1.5 border border-amber-300 rounded-lg bg-white font-black text-amber-950 text-sm"
                />
                <span className="text-[11px] text-amber-800">
                  chicks hatched from {batch.eggsAccepted} eggs set
                </span>
              </div>
            </div>
          )}

          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className="font-bold text-slate-700">Message Preview (Ready to Send):</label>
              <button
                onClick={handleCopy}
                className="text-[11px] font-semibold text-emerald-800 hover:text-emerald-900 flex items-center gap-1"
              >
                {copied ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                <span>{copied ? 'Copied to clipboard!' : 'Copy text'}</span>
              </button>
            </div>
            <pre className="p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-800 font-sans whitespace-pre-wrap leading-relaxed max-h-72 overflow-y-auto select-all">
              {currentMessage}
            </pre>
          </div>
        </div>

        {/* Modal Bottom Actions */}
        <div className="bg-slate-50 px-4 py-3 border-t border-slate-200 flex items-center justify-between shrink-0 text-xs">
          <span className="text-slate-500 text-[11px]">
            Target phone: <strong>{batch.customerPhone}</strong>
          </span>
          <div className="flex items-center gap-2">
            <button
              onClick={onClose}
              className="px-3 py-1.5 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-100"
            >
              Close
            </button>
            <a
              href={whatsAppUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="px-4 py-1.5 bg-emerald-800 hover:bg-emerald-700 text-white font-bold rounded-lg flex items-center gap-1.5 shadow-sm active:scale-95 transition"
            >
              <Share2 className="w-3.5 h-3.5" />
              <span>Send via WhatsApp</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};
