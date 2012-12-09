import java.util.*;

import javax.servlet.*;
import javax.servlet.http.*;

public class UploadLocation extends HttpServlet {

  private String email = "unknown";
  private String lat = "0";
  private String lon = "0";
  private String alt = "0";

  public void doPost(HttpServletRequest request,
                     HttpServletResponse response)
      throws ServletException, IOException {
    doGet(request, response);
  }

  public void doGet(HttpServletRequest request,
                    HttpServletResponse response)
      throws ServletException, IOException {

    response.setStatus(HttpServletResponse.SC_OK);
    String info = request.getParameter("info");
    if (info == null){
      email = (request.getParameter("email") != null)?
        request.getParameter("email") : email;
      lat = (request.getParameter("lat") != null)?
        request.getParameter("lat") : lat;
      lon = (request.getParameter("lon") != null)?
        request.getParameter("lon") : lon;
      alt = (request.getParameter("alt") != null)?
        request.getParameter("alt") : alt;
    }
    else {
      response.setContentType("text/plain");
      String json = "{\"info\": {" +
         "\"email\": \"" + email + "\", " +
         "\"lat\": " + lat + ", " +
         "\"lon\": " + lon + " , " +
         "\"alt\": " + alt + "} }";
      PrintWriter out = response.getWriter();
      out.print(json);
      out.close();
    }
  }
}
