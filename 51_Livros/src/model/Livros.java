package model;

public class Livros {

		// TODO Auto-generated method stub
		
			 private long isbn;
			 private String titulo;
			 private int edicao;
			 private String suporte;
			 private int paginas;
			 private String editora;
			 private String autor;
		
		     public long getIsbn() {
			 return isbn;
			 }
			 
			 @Override
			public String toString() {
				return "Livros [isbn=" + isbn + ", titulo=" + titulo + ", edicao=" + edicao + ", suporte=" + suporte
						+ ", paginas=" + paginas + ", editora=" + editora + ", autor=" + autor + "]";
			}

			public Livros(long isbn, String titulo, int edicao, String suporte, int paginas, String editora,
					String autor) {
				super();
				this.isbn = isbn;
				this.titulo = titulo;
				this.edicao = edicao;
				this.suporte = suporte;
				this.paginas = paginas;
				this.editora = editora;
				this.autor = autor;
			}

			public Livros() {
				super();
				// TODO Auto-generated constructor stub
			}

			public String getTitulo() {
			 return titulo;
			 }
			 
			 public int getEdicao() {
			 return edicao;
				 }
			 
			 public String getSuporte() {
				 return suporte;
				 }
			 
			 public int getPaginas() {
				 return paginas;
				 }
			 
			 public String getEditora() {
				 return editora;
			 } 
			 
			 public String getAutor() {
				return autor;
				 
}			 
	
}
