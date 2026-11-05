import React, { useState } from 'react';
import { usePWAInstall } from '../../hooks/usePWAInstall';
import { Download, Smartphone } from 'lucide-react';
import { ApkDownloadModal } from './ApkDownloadModal';

export const PWAInstallButton: React.FC = () => {
  const { isInstallable, isInstalled, isIOS, install } = usePWAInstall();
  const [showApkModal, setShowApkModal] = useState(false);

  return (
    <>
      <button
        onClick={() => setShowApkModal(true)}
        className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition active:scale-95"
        title="Download Msantha Farm Android APK or Package for Google Play Store"
      >
        <Smartphone className="w-3.5 h-3.5 text-amber-300" />
        <span className="hidden sm:inline">Get App (APK / Store)</span>
        <span className="sm:hidden">App</span>
      </button>

      {/* Full Android APK and Installation Modal */}
      <ApkDownloadModal isOpen={showApkModal} onClose={() => setShowApkModal(false)} />
    </>
  );
};
