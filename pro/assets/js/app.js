document.querySelectorAll('[data-barcode]').forEach((el) => {
    const code = el.getAttribute('data-barcode');
    if (!code || typeof JsBarcode === 'undefined') return;
    JsBarcode(el, code, {
        format: 'CODE128',
        lineColor: '#111',
        width: 2,
        height: 48,
        displayValue: true,
        margin: 4,
    });
});
