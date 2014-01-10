phpORMbr
========

ORM PHP simples para ser utilizado com MySQL. Foi desenvolvido para simplificar operações básicas de manipulação de dados. Utiliza padrões de JSON para mapear as funções das classes e objetos, para recuperar os padrões utiliza as funções de reflection do PHP.


Instalação
-------
Somente dois aquivos:
* BasicDao.class.php: Lógica do ORM
* ConnectionFactory.class.php: Singleton para a conexão com o BD

Classe Mapeada
-------

É simples mapear a classe, basta colocar as propriedades nos comentários.

```php
/**
 * "Tabela":"usuario"
 */
class Usuario {

    /**
     * "Coluna":"id",
     * "Tipo":"int",
     * "AutoIncremento":"sim",
     * "ChavePrimaria":"sim",
     * "Nulo":"nao"
     */    
    private $id;
    
    /**
     * "Coluna":"nome",
     * "Tipo":"varchar",
     * "Tamanho":80,
     * "Nulo":"nao"
     */    
    private $nome;
    
    /**
     * "Coluna":"email",
     * "Tipo":"varchar",
     * "Tamanho":80,
     * "Nulo":"nao"
     */    
    private $email;
    
    /**
     * "Coluna":"senha",
     * "Tipo":"varchar",
     * "Tamanho":20,
     * "Nulo":"nao"
     */    
    private $senha;
    
    /**
     * "Coluna":"tipo",
     * "Tipo":"int",
     * "Tamanho":1,
     * "Nulo":"nao"
     */    
    private $tipo;
    
    //getters e setters
    
}
```

Exemplos de uso
-------

Utilizando os métodos para manipular os dados para um CRUD simples.


### Importando
```php

    require './Usuario.class.php';
    require './dao/BasicDao.class.php';
    
    $dao = new BasicDao();
```

### Inserir
Basta criar o objeto, preencha as propriedades e **salvar**!
```php    
    //Inserir
    $u = new Usuario();
    $u->setNome("Fulano da Silva");
    $u->setEmail("fulano@gmail.com");
    $u->setSenha("123");
    $dao->salvar($u);
```

### Atualizar
Crie um objeto, preencha as propriedades e **salvar**! Não esqueça de preencher a propriedade marcada como Chave Primária! Se a chave primária estiver preenchida o método faz um UPDATE caso  contrário faz INSERT dos dados.

```php
    //Atualizar    
    $u = new Usuario();
    $u->setId(5);
    $u->setNome("Ciclado da Silva");
    $u->setEmail("ciclano@gmail.com");
    $u->setSenha("456");
    $dao->salvar($u);
```

### Recuperar
Crie um objeto que servirá de modelo para a pesquisa, no exemplo se mais de um registro possuir no campo email o valor "ciclano@gmail.com" ambos serão retornados. O retorno é uma array, por reflection o método **recuperar** retorna uma array do mesmo tipo do modelo que foi passado. 
```php
    //Recuperar    
    $u = new Usuario();
    $u->setEmail("ciclano@gmail.com");
    $usuarios = $dao->recuperar($u);
    foreach ($usuarios as $usuario) {
        echo $usuario->getId().'<br>';
        echo $usuario->getNome().'<br>';
        echo $usuario->getEmail().'<br><br>';
    }
```

### Excluir
O método **excluir** é bem simples, basta criar um objeto e preencher a propriedade da chave primária com o identificador que se desejado e pronto!
```php    
    //Excluir
    $u = new Usuario();
    $u->setId(5);
    $dao->excluir($u);

```

Próximos passos
-------
Muitas coisas ainda podem ser feitas, basta ajuda e colaboração para que o framework possa ficar mais robusto e com funções avançadas. Algumas sugestões de mudanças:

* Tratamento de Exceções;
* OneToMany, ManyToOne e ManyToMany;
* Integração com outros DBMSs;
* Trabalhar e Interpretar tipos de dados diferentes;
* E muito mais!
 
