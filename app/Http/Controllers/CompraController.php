<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index()
    {
        $vendas = Venda::with('produtos')
            ->where('cliente_id', Auth::id())
            ->latest()
            ->get();

        return view('compras.index', compact('vendas'));
    }

    public function create()
    {
        $produtos = Produto::orderBy('nome')->get();

        return view('compras.create', compact('produtos'));
    }

    public function store(Request $request)
    {
        $itensInput = $request->input('itens', []);
        $itensSelecionados = [];

        foreach ($itensInput as $produtoId => $quantidade) {
            $qtd = (int) $quantidade;
            if ($qtd > 0) {
                $itensSelecionados[(int) $produtoId] = $qtd;
            }
        }

        if (empty($itensSelecionados)) {
            return back()
                ->withErrors(['itens' => 'Selecione ao menos um produto com quantidade maior que zero.'])
                ->withInput();
        }

        $produtos = Produto::whereIn('id', array_keys($itensSelecionados))->get()->keyBy('id');

        foreach ($itensSelecionados as $produtoId => $qtd) {
            $produto = $produtos->get($produtoId);
            if (! $produto || $qtd > $produto->estoque) {
                $nomeProduto = $produto?->nome ?? "ID {$produtoId}";
                return back()
                    ->withErrors(['estoque' => "Quantidade indisponível em estoque para: {$nomeProduto}."])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($itensSelecionados, $produtos) {
            $venda = Venda::create(['cliente_id' => Auth::id()]);

            foreach ($itensSelecionados as $produtoId => $qtd) {
                $produto = $produtos->get($produtoId);
                $venda->produtos()->attach($produto->id, [
                    'quantidade' => $qtd,
                    'preco_unitario' => $produto->preco,
                ]);
                $produto->decrement('estoque', $qtd);
            }
        });

        return redirect()->route('compras.index')
            ->with('sucesso', 'Compra realizada!');
    }
}
