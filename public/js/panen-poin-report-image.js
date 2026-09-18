$(function () {
    const button = document.getElementById('saveReportImage');
    if (!button) return;

    const reportTable = document.getElementById(button.dataset.table);
    const period = document.getElementById(button.dataset.period);
    if (!reportTable || !period) return;

    let loading = true;
    let saving = false;
    let failed = false;
    const updateButton = () => {
        button.disabled = loading || saving || failed;
        button.title = failed
            ? 'Data report gagal dimuat. Pilih ulang periode untuk mencoba kembali.'
            : loading ? 'Menunggu data report selesai dimuat.' : '';
    };

    // The helper can load after DataTables has already drawn the report.
    if ($.fn.dataTable && $.fn.dataTable.isDataTable(reportTable)) {
        const settings = $(reportTable).DataTable().settings()[0];
        loading = !settings._bInitComplete || !!(settings.jqXHR && settings.jqXHR.readyState !== 4);
        failed = !!(settings.jqXHR && settings.jqXHR.readyState === 4 && !settings.json);
    }

    $(reportTable).on('preXhr.dt', function () {
        loading = true;
        failed = false;
        updateButton();
    }).on('draw.dt init.dt', function () {
        loading = false;
        updateButton();
    }).on('xhr.dt', function (event, settings, json) {
        failed = !json || !!json.error;
        if (failed) loading = false;
        updateButton();
    }).on('error.dt', function () {
        loading = false;
        failed = true;
        updateButton();
    });
    updateButton();

    button.addEventListener('click', async function () {
        if (loading || saving || failed) return;
        saving = true;
        updateButton();
        const originalLabel = button.innerHTML;
        const periodValue = period.value;
        const periodDisabled = period.disabled;
        button.textContent = 'Menyimpan...';
        period.disabled = true;
        let capture;
        let downloadUrl;

        try {
            if (typeof html2canvas !== 'function') throw new Error('Image library unavailable');
            capture = document.createElement('div');
            Object.assign(capture.style, {
                position: 'absolute', left: '-10000px', top: '0',
                width: Math.max(1100, reportTable.scrollWidth) + 'px',
                padding: '20px', background: '#ffffff'
            });
            const tableCopy = reportTable.cloneNode(true);
            tableCopy.removeAttribute('id');
            tableCopy.querySelectorAll('[id]').forEach(element => element.removeAttribute('id'));
            tableCopy.querySelectorAll('tr.child').forEach(row => row.remove());
            // Include columns hidden by DataTables on narrow screens.
            tableCopy.querySelectorAll('th, td').forEach(cell => {
                cell.style.setProperty('display', 'table-cell', 'important');
                cell.classList.remove('dtr-hidden');
            });
            capture.appendChild(tableCopy);
            document.body.appendChild(capture);
            if (document.fonts) await document.fonts.ready;

            const canvas = await html2canvas(capture, {
                backgroundColor: '#ffffff', scale: 2, useCORS: true,
                windowWidth: Math.max(1280, capture.scrollWidth), logging: false
            });
            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob(value => value ? resolve(value) : reject(new Error('Empty image')), 'image/png');
            });
            downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'Panen_Poin_V4_Report_' + button.dataset.report + '_' + periodValue.replace(/[^a-zA-Z0-9_-]/g, '_') + '.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error('Failed to save report image:', error);
            alert('Gagal menyimpan gambar report. Silakan coba kembali.');
        } finally {
            if (capture) capture.remove();
            if (downloadUrl) setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
            period.disabled = periodDisabled;
            button.innerHTML = originalLabel;
            saving = false;
            updateButton();
        }
    });
});
