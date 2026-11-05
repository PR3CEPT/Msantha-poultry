import React from 'react';
import { useOnlineStatus } from '../../hooks/useOnlineStatus';
import { WifiOff, ShieldCheck } from 'lucide-react';

export const OfflineBanner: React.FC = () => {
  const isOnline = useOnlineStatus();

  if (isOnline) return null;

  return (
    <div className="bg-amber-800 text-amber-50 px-3 py-2 text-xs flex items-center justify-between border-b border-amber-900 sticky top-0 z-50 shadow-sm">
      <div className="flex items-center gap-2">
        <WifiOff className="w-4 h-4 text-amber-300 animate-pulse shrink-0" />
        <span className="font-medium">
          Offline Mode Active — Working locally without internet in Liwonde. All records are safely saved on this device.
        </span>
      </div>
      <div className="flex items-center gap-1 text-[11px] bg-amber-900/60 px-2 py-0.5 rounded text-amber-200">
        <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
        <span>Local DB Secure</span>
      </div>
    </div>
  );
};
