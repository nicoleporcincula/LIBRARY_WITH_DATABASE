const books = [
    { title: "The Whispering Oaks", author: "E. Vance" },
    { title: "Maps of the Forgotten", author: "R. Croft" },
    { title: "Ancient Runes", author: "A. Black" },
    { title: "Stargazer's Tale", author: "L. Wren" },
    { title: "Lost Kingdoms", author: "J. Thorne" }
];

const container = document.getElementById('booksContainer');

books.forEach(book => {
    const div = document.createElement('div');
    div.className = 'book';

    div.innerHTML = `
        <h4>${book.title}</h4>
        <p>${book.author}</p>
    `;

    container.appendChild(div);
});