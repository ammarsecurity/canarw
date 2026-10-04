/* Extra tools for WordPress's bundled visual editor. No external editor service. */
(() => {
    'use strict';
    window.CanarwEditorTools = () => {
        const mce = window.tinymce;
        if (!mce || mce.PluginManager.get('canarwtools')) return;
        mce.addI18n('ar', {
            _dir: 'rtl', 'Undo': 'تراجع', 'Redo': 'إعادة', 'Bold': 'عريض', 'Italic': 'مائل',
            'Underline': 'تسطير', 'Strikethrough': 'شطب', 'Superscript': 'حرف علوي', 'Subscript': 'حرف سفلي',
            'Bullet list': 'قائمة نقطية', 'Numbered list': 'قائمة مرقمة', 'Blockquote': 'اقتباس',
            'Align left': 'محاذاة لليسار', 'Align center': 'توسيط', 'Align right': 'محاذاة لليمين', 'Justify': 'ضبط المحاذاة',
            'Increase indent': 'زيادة المسافة البادئة', 'Decrease indent': 'تقليل المسافة البادئة',
            'Formats': 'أنماط', 'Format': 'تنسيق', 'Font Family': 'الخط', 'Font Sizes': 'الحجم',
            'Text color': 'لون النص', 'Background color': 'لون التمييز', 'Remove formatting': 'إزالة التنسيق',
            'Insert/edit link': 'إدراج أو تعديل رابط', 'Remove link': 'إزالة الرابط', 'Insert link': 'إدراج رابط',
            'Horizontal line': 'خط فاصل', 'Special character': 'رموز خاصة', 'Insert special character': 'إدراج رمز خاص',
            'Fullscreen': 'ملء الشاشة', 'Paste as text': 'لصق كنص', 'Right to left': 'من اليمين إلى اليسار', 'Left to right': 'من اليسار إلى اليمين',
            'Ok': 'موافق', 'OK': 'موافق', 'Cancel': 'إلغاء', 'Close': 'إغلاق', 'Apply': 'تطبيق',
            'Custom color': 'لون مخصص', 'No color': 'بدون لون', 'Color': 'لون', 'More colors': 'ألوان إضافية',
            'URL': 'الرابط', 'Text to display': 'النص الظاهر', 'Title': 'العنوان', 'Target': 'فتح الرابط',
            'None': 'بدون', 'New window': 'نافذة جديدة', 'Insert': 'إدراج', 'Edit': 'تعديل', 'Image': 'صورة',
            'Source': 'المصدر', 'Image description': 'وصف الصورة', 'Dimensions': 'الأبعاد', 'Constrain proportions': 'الحفاظ على النسبة'
        });
        mce.PluginManager.add('canarwtools', function(editor) {
            const cell = () => editor.dom.getParent(editor.selection.getNode(), 'td,th');
            const currentTable = () => editor.dom.getParent(editor.selection.getNode(), 'table');
            const transact = action => {
                editor.undoManager.transact(action);
                editor.nodeChanged();
                editor.fire('change');
            };
            const requireTable = () => {
                const table = currentTable();
                if (!table || !cell()) {
                    editor.windowManager.alert('ضع المؤشر داخل خلية الجدول أولاً.');
                    return null;
                }
                if ([...table.querySelectorAll('td,th')].some(c => c.rowSpan > 1 || c.colSpan > 1)) {
                    editor.windowManager.alert('هذا الجدول يحتوي خلايا مدمجة. عدّل بنيته من تبويب HTML للحفاظ عليها.');
                    return null;
                }
                return table;
            };
            const insertTable = () => editor.windowManager.open({
                title: 'إدراج جدول',
                body: [
                    {type: 'textbox', name: 'rows', label: 'عدد صفوف المحتوى', value: '3'},
                    {type: 'textbox', name: 'columns', label: 'عدد الأعمدة', value: '3'},
                    {type: 'checkbox', name: 'header', label: 'إضافة صف عناوين', checked: true}
                ],
                onsubmit: e => {
                    const rows = Number(e.data.rows), columns = Number(e.data.columns);
                    if (!Number.isInteger(rows) || !Number.isInteger(columns) || rows < 1 || rows > 20 || columns < 1 || columns > 8) {
                        e.preventDefault();
                        editor.windowManager.alert('اختر من 1 إلى 20 صفاً ومن 1 إلى 8 أعمدة.');
                        return;
                    }
                    let html = '<table class="canarw-table" style="width:100%">';
                    if (e.data.header) html += '<thead><tr>' + '<th scope="col">عنوان</th>'.repeat(columns) + '</tr></thead>';
                    html += '<tbody>' + ('<tr>' + '<td><br></td>'.repeat(columns) + '</tr>').repeat(rows) + '</tbody></table><p><br></p>';
                    editor.insertContent(html);
                }
            });
            const addRow = before => {
                const table = requireTable();
                if (!table) return;
                const selected = cell(), row = selected.parentNode;
                transact(() => {
                    const next = editor.getDoc().createElement('tr');
                    [...row.cells].forEach(() => { const td = editor.getDoc().createElement('td'); td.innerHTML = '<br>'; next.appendChild(td); });
                    if (row.parentNode.tagName === 'THEAD') {
                        const body = table.tBodies[0] || table.createTBody();
                        body.insertBefore(next, body.firstChild);
                    } else row.parentNode.insertBefore(next, before ? row : row.nextSibling);
                    editor.selection.setCursorLocation(next.cells[0], 0);
                });
            };
            const addColumn = before => {
                const table = requireTable();
                if (!table) return;
                const index = cell().cellIndex + (before ? 0 : 1);
                transact(() => [...table.rows].forEach(row => {
                    const next = editor.getDoc().createElement(row.parentNode.tagName === 'THEAD' ? 'th' : 'td');
                    if (next.tagName === 'TH') next.setAttribute('scope', 'col');
                    next.innerHTML = '<br>';
                    row.insertBefore(next, row.cells[index] || null);
                }));
            };
            const deleteRow = () => {
                const table = requireTable();
                if (!table) return;
                const row = cell().parentNode;
                transact(() => { row.remove(); if (!table.rows.length) table.remove(); editor.selection.select(editor.getBody(), true); editor.selection.collapse(false); });
            };
            const deleteColumn = () => {
                const table = requireTable();
                if (!table) return;
                const index = cell().cellIndex;
                transact(() => { [...table.rows].forEach(row => row.cells[index]?.remove()); if (!table.querySelector('td,th')) table.remove(); editor.selection.select(editor.getBody(), true); editor.selection.collapse(false); });
            };
            editor.addButton('canarwtable', {
                type: 'menubutton', text: 'جدول', icon: false, tooltip: 'إدراج وتعديل جدول',
                menu: [
                    {text: 'إدراج جدول جديد', onclick: insertTable},
                    {text: 'صف قبل الخلية', onclick: () => addRow(true)},
                    {text: 'صف بعد الخلية', onclick: () => addRow(false)},
                    {text: 'عمود قبل الخلية', onclick: () => addColumn(true)},
                    {text: 'عمود بعد الخلية', onclick: () => addColumn(false)},
                    {text: 'حذف الصف', onclick: deleteRow},
                    {text: 'حذف العمود', onclick: deleteColumn},
                    {text: 'حذف الجدول', onclick: () => { const table = currentTable(); if (table) transact(() => table.remove()); }}
                ]
            });
            const findReplace = replace => editor.windowManager.open({
                title: replace ? 'استبدال النص' : 'البحث في النص',
                body: [
                    {type: 'textbox', name: 'query', label: 'ابحث عن'},
                    ...(replace ? [{type: 'textbox', name: 'replacement', label: 'استبدل بـ'}] : []),
                    {type: 'checkbox', name: 'matchCase', label: 'مطابقة الأحرف الكبيرة والصغيرة', checked: false}
                ],
                onsubmit: e => {
                    if (!e.data.query) return;
                    const expression = new RegExp(e.data.query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), e.data.matchCase ? 'g' : 'gi');
                    const walker = editor.getDoc().createTreeWalker(editor.getBody(), 4), nodes = [];
                    let node, count = 0;
                    while ((node = walker.nextNode())) {
                        if (!editor.dom.getParent(node, '[contenteditable="false"]')) nodes.push(node);
                    }
                    if (replace) {
                        transact(() => nodes.forEach(n => {
                            expression.lastIndex = 0;
                            n.nodeValue = n.nodeValue.replace(expression, () => { count++; return e.data.replacement || ''; });
                        }));
                        editor.windowManager.alert(count ? 'تم استبدال ' + count + ' موضع.' : 'لا يوجد نص مطابق.');
                    } else {
                        for (const n of nodes) {
                            expression.lastIndex = 0;
                            const match = expression.exec(n.nodeValue);
                            if (!match) continue;
                            const range = editor.getDoc().createRange();
                            range.setStart(n, match.index); range.setEnd(n, match.index + match[0].length);
                            editor.selection.setRng(range); n.parentElement.scrollIntoView({block: 'nearest'}); count = 1; break;
                        }
                        if (!count) editor.windowManager.alert('لا يوجد نص مطابق.');
                    }
                }
            });
            editor.addButton('canarwfind', {
                type: 'menubutton', text: 'بحث', icon: false, tooltip: 'البحث والاستبدال',
                menu: [{text: 'بحث في النص', onclick: () => findReplace(false)}, {text: 'استبدال كل المطابقات', onclick: () => findReplace(true)}]
            });
            editor.addButton('canarwhelp', {
                text: 'مساعدة', icon: false, tooltip: 'دليل أدوات الكتابة',
                onclick: () => editor.windowManager.open({
                    title: 'الكتابة والتنسيق بسهولة',
                    body: [{type: 'container', html: '<div style="padding:8px;line-height:2;max-width:480px;direction:rtl;text-align:right"><p>حدّد النص، ثم اختر التنسيق من الشريط. إضافة وسائط تفتح مكتبة ووردبريس للصور والمعارض وملفات الصوت والفيديو.</p><p>من «جدول» تستطيع إدراج جدول وتعديل صفوفه وأعمدته. اتجاه الفقرة قابل للتغيير عند كتابة نص مختلط.</p><p><strong>Ctrl / ⌘ + Z</strong> تراجع، <strong>Ctrl / ⌘ + Y</strong> إعادة، <strong>Ctrl / ⌘ + B</strong> عريض، <strong>Ctrl / ⌘ + I</strong> مائل. <strong>Alt + F10</strong> ينقل التركيز إلى الأدوات.</p><p>تبويب HTML اختياري للمستخدم المتقدم. احفظ الصفحة لإظهار التعديلات في الموقع.</p></div>'}]
                })
            });
            return {name: 'CANARW editor tools'};
        });
    };
})();
