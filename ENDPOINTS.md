# API Endpoints

## List of Endpoints

1. **GET /api/users**  
   - Description: Retrieves a list of users.  
   - Response: 200 OK

2. **POST /api/users**  
   - Description: Creates a new user.  
   - Request Body: {"name": "string", "email": "string"}
   - Response: 201 Created

3. **GET /api/users/{id}**  
   - Description: Retrieves a user by ID.  
   - Response: 200 OK or 404 Not Found

4. **PUT /api/users/{id}**  
   - Description: Updates a user by ID.  
   - Request Body: {"name": "string", "email": "string"}
   - Response: 200 OK or 404 Not Found

5. **DELETE /api/users/{id}**  
   - Description: Deletes a user by ID.  
   - Response: 204 No Content