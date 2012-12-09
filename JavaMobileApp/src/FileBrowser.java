// Decompiled by DJ v3.11.11.95 Copyright 2009 Atanas Neshkov  Date: 1/24/2010 3:23:26 PM
// Home Page: http://members.fortunecity.com/neshkov/dj.html  http://www.neshkov.com/dj.html - Check often for new version!
// Decompiler options: packimports(3) 

import java.io.*;
import java.util.*;
import javax.microedition.io.Connector;
import javax.microedition.io.file.FileConnection;
import javax.microedition.io.file.FileSystemRegistry;
import javax.microedition.lcdui.*;

public class FileBrowser
    implements Runnable, CommandListener
{

    public FileBrowser(GuiTests2 guitests2)
    {
        view = new Command("View", 8, 1);
        creat = new Command("New", 8, 2);
        delete = new Command("Delete", 8, 3);
        creatOK = new Command("OK", 4, 1);
        prop = new Command("Properties", 8, 2);
        back = new Command("Back", 2, 2);
        exit = new Command("Back", 7, 3);
        parent = guitests2;
        currDirName = "/";
        try
        {
            dirIcon = Image.createImage("/icons/dir.png");
        }
        catch(IOException ioexception)
        {
            dirIcon = null;
        }
        try
        {
            fileIcon = Image.createImage("/icons/file.png");
        }
        catch(IOException ioexception1)
        {
            fileIcon = null;
        }
        iconList = (new Image[] {
            fileIcon, dirIcon
        });
    }

    public void run()
    {
        try
        {
            showCurrDir();
        }
        catch(SecurityException securityexception)
        {
            Alert alert = new Alert("Error", "You are not authorized to access the restricted API", null, AlertType.ERROR);
            alert.setTimeout(-2);
            Form form = new Form("Cannot access FileConnection");
            form.append(new StringItem(null, "You cannot run this MIDlet with the current permissions. Sign the MIDlet suite, or run it in a different security domain"));
            form.addCommand(exit);
            form.setCommandListener(this);
            Display.getDisplay(parent).setCurrent(alert, form);
        }
        catch(Exception exception)
        {
            exception.printStackTrace();
        }
    }

    public void commandAction(Command command, Displayable displayable)
    {
        if(command == view)
        {
            List list = (List)displayable;
            final String currFile = list.getString(list.getSelectedIndex());
            (new Thread(new Runnable() {

                public void run()
                {
                    if(currFile.endsWith("/") || currFile.equals(".."))
                        traverseDirectory(currFile);
                    else
                        showFile(currFile);
                }

            }
)).start();
        } else
        if(command == prop)
        {
            List list1 = (List)displayable;
            String s1 = list1.getString(list1.getSelectedIndex());
            showProperties(s1);
        } else
        if(command == creat)
            createFile();
        else
        if(command == creatOK)
        {
            String s = nameInput.getString();
            if(s == null || s.equals(""))
            {
                Alert alert = new Alert("Error!", "File Name is empty. Please provide file name", null, AlertType.ERROR);
                alert.setTimeout(-2);
                Display.getDisplay(parent).setCurrent(alert);
            } else
            {
                executeCreateFile(s, typeInput.getSelectedIndex() != 0);
                Display.getDisplay(parent).getCurrent().removeCommand(creatOK);
                Display.getDisplay(parent).getCurrent().removeCommand(back);
            }
        } else
        if(command == back)
            showCurrDir();
        else
        if(command == exit)
            parent.mainMenu();
        else
        if(command == delete)
        {
            List list2 = (List)displayable;
            String s2 = list2.getString(list2.getSelectedIndex());
            executeDelete(s2);
        }
    }

    void delete(String s)
    {
        if(!s.equals(".."))
        {
            if(s.endsWith("/"))
            {
                checkDeleteFolder(s);
            } else
            {
                deleteFile(s);
                showCurrDir();
            }
        } else
        {
            Alert alert = new Alert("Error!", "Can not delete The up-directory (..) symbol! not a real folder", null, AlertType.ERROR);
            alert.setTimeout(-2);
            Display.getDisplay(parent).setCurrent(alert);
        }
    }

    private void executeDelete(String s)
    {
        final String file = s;
        (new Thread(new Runnable() {

            public void run()
            {
                delete(file);
            }

        }
)).start();
    }

    private void checkDeleteFolder(String s)
    {
        try
        {
            FileConnection fileconnection = (FileConnection)Connector.open("file:///" + currDirName + s);
            Enumeration enumeration = fileconnection.list("*", true);
            if(!enumeration.hasMoreElements())
            {
                fileconnection.delete();
                showCurrDir();
            } else
            {
                Alert alert = new Alert("Error!", "Can not delete The non-empty folder: " + s, null, AlertType.ERROR);
                alert.setTimeout(-2);
                Display.getDisplay(parent).setCurrent(alert);
            }
        }
        catch(IOException ioexception)
        {
            System.out.println(currDirName + s);
            ioexception.printStackTrace();
        }
    }

    private void executeCreateFile(final String name, final boolean val)
    {
        (new Thread(new Runnable() {

            public void run()
            {
                createFile(name, val);
            }

        }
)).start();
    }

    void showCurrDir()
    {
        FileConnection fileconnection = null;
        try
        {
            Enumeration enumeration;
            List list;
            if("/".equals(currDirName))
            {
                enumeration = FileSystemRegistry.listRoots();
                list = new List(currDirName, 3);
            } else
            {
                fileconnection = (FileConnection)Connector.open("file:///" + currDirName);
                enumeration = fileconnection.list();
                list = new List(currDirName, 3);
                list.append("..", dirIcon);
            }
            while(enumeration.hasMoreElements()) 
            {
                String s = (String)enumeration.nextElement();
                if(s.charAt(s.length() - 1) == '/')
                    list.append(s, dirIcon);
                else
                    list.append(s, fileIcon);
            }
            list.setSelectCommand(view);
            if(!"/".equals(currDirName))
            {
                list.addCommand(prop);
                list.addCommand(creat);
                list.addCommand(delete);
            }
            list.addCommand(exit);
            list.setCommandListener(this);
            if(fileconnection != null)
                fileconnection.close();
            Display.getDisplay(parent).setCurrent(list);
        }
        catch(IOException ioexception)
        {
            ioexception.printStackTrace();
        }
    }

    void traverseDirectory(String s)
    {
        if(currDirName.equals("/"))
        {
            if(s.equals(".."))
                return;
            currDirName = s;
        } else
        if(s.equals(".."))
        {
            int i = currDirName.lastIndexOf('/', currDirName.length() - 2);
            if(i != -1)
                currDirName = currDirName.substring(0, i + 1);
            else
                currDirName = "/";
        } else
        {
            currDirName = currDirName + s;
        }
        showCurrDir();
    }

    void showFile(String s)
    {
        try
        {
            FileConnection fileconnection = (FileConnection)Connector.open("file:///" + currDirName + s);
            if(!fileconnection.exists())
                throw new IOException("File does not exists");
            InputStream inputstream = fileconnection.openInputStream();
            byte abyte0[] = new byte[1024];
            int i = inputstream.read(abyte0, 0, 1024);
            inputstream.close();
            fileconnection.close();
            TextBox textbox = new TextBox("View File: " + s, null, 1024, 0x20000);
            textbox.addCommand(back);
            textbox.addCommand(exit);
            textbox.setCommandListener(this);
            if(i > 0)
                textbox.setString(new String(abyte0, 0, i));
            Display.getDisplay(parent).setCurrent(textbox);
        }
        catch(Exception exception)
        {
            Alert alert = new Alert("Error!", "Can not access file " + s + " in directory " + currDirName + "\nException: " + exception.getMessage(), null, AlertType.ERROR);
            alert.setTimeout(-2);
            Display.getDisplay(parent).setCurrent(alert);
        }
    }

    void deleteFile(String s)
    {
        try
        {
            FileConnection fileconnection = (FileConnection)Connector.open("file:///" + currDirName + s);
            fileconnection.delete();
        }
        catch(Exception exception)
        {
            Alert alert = new Alert("Error!", "Can not access/delete file " + s + " in directory " + currDirName + "\nException: " + exception.getMessage(), null, AlertType.ERROR);
            alert.setTimeout(-2);
            Display.getDisplay(parent).setCurrent(alert);
        }
    }

    void showProperties(String s)
    {
        if(s.equals(".."))
            return;
        try
        {
            FileConnection fileconnection = (FileConnection)Connector.open("file://localhost/" + currDirName + s);
            if(!fileconnection.exists())
                throw new IOException("File does not exists");
            Form form = new Form("Properties: " + s);
            ChoiceGroup choicegroup = new ChoiceGroup("Attributes:", 2, attrList, null);
            choicegroup.setSelectedFlags(new boolean[] {
                fileconnection.canRead(), fileconnection.canWrite(), fileconnection.isHidden()
            });
            form.append(new StringItem("Location:", currDirName));
            form.append(new StringItem("Type: ", fileconnection.isDirectory() ? "Directory" : "Regular File"));
            form.append(new StringItem("Modified:", myDate(fileconnection.lastModified())));
            form.append(choicegroup);
            form.addCommand(back);
            form.addCommand(exit);
            form.setCommandListener(this);
            fileconnection.close();
            Display.getDisplay(parent).setCurrent(form);
        }
        catch(Exception exception)
        {
            Alert alert = new Alert("Error!", "Can not access file " + s + " in directory " + currDirName + "\nException: " + exception.getMessage(), null, AlertType.ERROR);
            alert.setTimeout(-2);
            Display.getDisplay(parent).setCurrent(alert);
        }
        return;
    }

    void createFile()
    {
        Form form = new Form("New File");
        nameInput = new TextField("Enter Name", null, 256, 0);
        typeInput = new ChoiceGroup("Enter File Type", 1, typeList, iconList);
        form.append(nameInput);
        form.append(typeInput);
        form.addCommand(creatOK);
        form.addCommand(back);
        form.addCommand(exit);
        form.setCommandListener(this);
        Display.getDisplay(parent);
    }

    void createFile(String s, boolean flag)
    {
        try
        {
            FileConnection fileconnection = (FileConnection)Connector.open("file:///" + currDirName + s);
            if(flag)
                fileconnection.mkdir();
            else
                fileconnection.create();
            showCurrDir();
        }
        catch(Exception exception)
        {
            String s1 = "Can not create file '" + s + "'";
            if(exception.getMessage() != null && exception.getMessage().length() > 0)
                s1 = s1 + "\n" + exception;
            Alert alert = new Alert("Error!", s1, null, AlertType.ERROR);
            alert.setTimeout(-2);
            Display.getDisplay(parent).setCurrent(alert);
            Display.getDisplay(parent).getCurrent().addCommand(creatOK);
            Display.getDisplay(parent).getCurrent().addCommand(back);
        }
    }

    private String myDate(long l)
    {
        Calendar calendar = Calendar.getInstance();
        calendar.setTime(new Date(l));
        StringBuffer stringbuffer = new StringBuffer();
        stringbuffer.append(calendar.get(11));
        stringbuffer.append(':');
        stringbuffer.append(calendar.get(12));
        stringbuffer.append(':');
        stringbuffer.append(calendar.get(13));
        stringbuffer.append(',');
        stringbuffer.append(' ');
        stringbuffer.append(calendar.get(5));
        stringbuffer.append(' ');
        stringbuffer.append(monthList[calendar.get(2)]);
        stringbuffer.append(' ');
        stringbuffer.append(calendar.get(1));
        return stringbuffer.toString();
    }

    GuiTests2 parent;
    private static final String attrList[] = {
        "Read", "Write", "Hidden"
    };
    private static final String typeList[] = {
        "Regular File", "Directory"
    };
    private static final String monthList[] = {
        "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", 
        "Nov", "Dec"
    };
    private static final String UP_DIRECTORY = "..";
    private static final String MEGA_ROOT = "/";
    private static final String SEP_STR = "/";
    private static final char SEP = 47;
    private String currDirName;
    private Command view;
    private Command creat;
    private Command delete;
    private Command creatOK;
    private Command prop;
    private Command back;
    private Command exit;
    private TextField nameInput;
    private ChoiceGroup typeInput;
    private Image dirIcon;
    private Image fileIcon;
    private Image iconList[];

}
