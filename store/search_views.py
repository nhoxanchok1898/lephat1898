import json
from elasticsearch import Elasticsearch

class Search:
    def __init__(self):
        self.es = Elasticsearch(['http://localhost:9200'])  # Replace with your Elasticsearch server

    def full_text_search(self, index, query):
        response = self.es.search(index=index, body={'query': {'match': {'content': query}}})
        return response['hits']['hits']

    def faceted_search(self, index, query, filters):
        query_body = {
            'query': {'match': {'content': query}},
            'aggs': {filter_name: {'terms': {'field': filter_value}} for filter_name, filter_value in filters.items()}
        }
        response = self.es.search(index=index, body=query_body)
        return response['hits']['hits'], response['aggregations']

    def autocomplete_suggestions(self, index, prefix):
        response = self.es.search(index=index, body={
            'suggest': {
                'autocomplete-suggest': {
                    'prefix': prefix,
                    'completion': {'field': 'suggest'}
                }
            }
        })
        return response['suggest']['autocomplete-suggest'][0]['options']

    def track_search(self, user_id, search_term):
        # This function can be expanded to include tracking logic (e.g., storing in a database)
        print(f'Tracking search: User: {user_id}, Search Term: {search_term}')