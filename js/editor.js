tinymce.init({
	license_key: 'gpl',
    selector: 'textarea[name="notes"]',
    height: 300,
    menubar: false,
    branding: false,
    plugins: ['lists', 'link', 'table', 'code'],
    toolbar: 'undo redo | bold italic | bullist numlist | link | table | code',
    content_style: "body { font-family: Arial, sans-serif; font-size:14px }"
});