function parseHTMLToJSON(htmlString) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlString, 'text/html');

    function processList(ul) {
        const result = {};
        const items = ul.querySelectorAll(':scope > li');
        items.forEach(item => {
            const title = item.querySelector('a').textContent.trim();
            const nestedList = item.querySelector('ul');
            if (nestedList) {
                result[title] = processList(nestedList);
            } else {
                if (!result["papers"]) result["papers"] = [];
                result["papers"].push(title);
            }
        });
        return result;
    }

    const rootList = doc.querySelector('ul.multi-level');
    return processList(rootList);
}