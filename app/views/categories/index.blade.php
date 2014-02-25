@extends('master')

@section('content')

  @if( count($categories) > 0 )
    
    @foreach($categories as $key => $category)
      <div class="list">
    		<h5 class="pull-left">
          <a href="{{ URL::to('categories/' . $category->id . '/edit') }}"><strong>{{ $category->name }}</strong></a>
        </h5>
        
        <div class="list-actions pull-right">   
          <a href="{{ URL::to('categories/' . $category->id . '/edit') }}" class="text-muted" title="{{ trans('common.edit') }}"><span class="fa fa-pencil"></span></a>
				  <a href="{{ URL::to('categories/' . $category->id) }}" data-method="delete" data-object="category" class="text-muted" title="{{ trans('common.delete') }}"><span class="fa fa-trash-o"></span></a>
          <span class="badge pull-right">{{ $category->notes }}</span>
        </div>
        <div class="clearfix"></div>
    	</div>
    @endforeach
    
    {{ $categories->links(); }}
    
  @else
  
    <p class="empty-list"><a href="{{ URL::to('categories/create') }}">{{ trans('common.create_first_category') }}</a></p>
    
  @endif
  
@stop